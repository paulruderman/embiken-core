<?php

namespace App\Http\Controllers;

use App\Models\BillingPlan;
use App\Models\EarlyAccessInvitation;
use App\Models\MarketingPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class MarketingPageController extends Controller
{
    public function home(): InertiaResponse
    {
        return inertia('Marketing/Home', [
            'earlyAccessUi' => __('app.early_access_marketing'),
        ]);
    }

    public function pricing(): InertiaResponse
    {
        return $this->pricingPage();
    }

    public function invite(string $token, Request $request): RedirectResponse
    {
        $invitation = EarlyAccessInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
        abort_unless($invitation->isUsable(), 404);
        $request->session()->put('early_access_invitation_token', $token);

        return redirect('/pricing');
    }

    public function waitlist(): InertiaResponse
    {
        return inertia('Marketing/Waitlist', [
            'ui' => __('app.early_access_waitlist'),
        ]);
    }

    private function pricingPage(): InertiaResponse
    {
        $page = $this->publishedPage('pricing');
        $plans = BillingPlan::query()->where('is_public', true)->orderBy('sort_order')->get();

        return inertia('Marketing/Pricing', [
            'invitationAvailable' => $this->sessionHasUsableInvitation(request()),
            'earlyAccessUi' => __('app.early_access_marketing'),
            'plans' => $plans->map(fn (BillingPlan $plan): array => [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'monthlyAmountCents' => $plan->monthly_amount_cents,
                'annualAmountCents' => $plan->annual_amount_cents,
                'currency' => strtoupper($plan->currency),
                'processingRateBasisPoints' => $plan->processing_rate_basis_points,
                'customPricing' => $plan->custom_pricing,
                'features' => $plan->features ?? [],
            ])->values(),
            'page' => $this->pagePayload($page),
        ]);
    }

    public function signup(string $billingPlan, Request $request): InertiaResponse
    {
        $token = $request->session()->get('early_access_invitation_token');
        abort_unless(is_string($token) && $token !== '', 404);

        $invitation = EarlyAccessInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
        abort_unless($invitation->isUsable(), 404);
        $plan = BillingPlan::query()->where('slug', $billingPlan)->where('is_public', true)->firstOrFail();

        return inertia('Marketing/Signup', [
            'plan' => [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'monthlyAmountCents' => $plan->monthly_amount_cents,
                'annualAmountCents' => $plan->annual_amount_cents,
                'currency' => strtoupper($plan->currency),
                'processingRateBasisPoints' => $plan->processing_rate_basis_points,
                'customPricing' => $plan->custom_pricing,
                'features' => $plan->features ?? [],
            ],
            'domainSuffix' => config('tenancy.central_domains.0', 'localhost'),
            'contactEmail' => config('marketing.contact_email'),
            'ui' => __('app.signup_ui'),
        ]);
    }

    public function checkoutPlaceholder(Request $request): InertiaResponse
    {
        abort_unless($request->session()->has('signup_plan'), 404);

        return inertia('Marketing/CheckoutPlaceholder', [
            'plan' => (string) $request->session()->get('signup_plan'),
            'domain' => (string) $request->session()->get('signup_domain'),
            'email' => (string) $request->session()->get('signup_email'),
        ]);
    }

    public function show(string $slug): InertiaResponse
    {
        return $this->renderContentPage($this->publishedPage($slug, 'page'));
    }

    public function blogIndex(): InertiaResponse
    {
        $page = $this->publishedPage('blog', 'page');
        $articles = MarketingPage::query()
            ->published()
            ->where('page_type', 'article')
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->get();

        return inertia('Marketing/BlogIndex', [
            'page' => $this->pagePayload($page),
            'articles' => $articles->map(fn (MarketingPage $article): array => [
                'slug' => $article->slug,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'publishedAt' => $article->published_at?->toDateString(),
                'canonicalUrl' => url('/blog/'.$article->slug),
            ])->values(),
        ]);
    }

    public function blogArticle(string $slug): InertiaResponse
    {
        return $this->renderContentPage($this->publishedPage($slug, 'article'), true);
    }

    public function sitemap(): Response
    {
        $paths = MarketingPage::query()
            ->published()
            ->get(['slug', 'page_type', 'updated_at'])
            ->map(function (MarketingPage $page): array {
                $path = match (true) {
                    $page->page_type === 'article' => '/blog/'.$page->slug,
                    $page->slug === 'home' => '/',
                    $page->slug === 'blog' => '/blog',
                    default => '/'.$page->slug,
                };

                return ['url' => url($path), 'updatedAt' => $page->updated_at?->toAtomString()];
            })
            ->push(['url' => url('/pricing'), 'updatedAt' => now()->toAtomString()]);

        $paths->push(['url' => url('/waitlist'), 'updatedAt' => now()->toAtomString()]);

        $urls = $paths->map(fn (array $entry): string => '<url><loc>'.e($entry['url']).'</loc><lastmod>'.e($entry['updatedAt'] ?? now()->toAtomString()).'</lastmod></url>')->implode('');

        return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$urls.'</urlset>')
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function publishedPage(string $slug, ?string $type = null): MarketingPage
    {
        return MarketingPage::query()
            ->published()
            ->when($type !== null, fn ($query) => $query->where('page_type', $type))
            ->where('slug', $slug)
            ->firstOrFail();
    }

    private function renderContentPage(MarketingPage $page, bool $isArticle = false): InertiaResponse
    {
        return inertia('Marketing/ContentPage', [
            'page' => [...$this->pagePayload($page), 'isArticle' => $isArticle],
        ]);
    }

    /** @return array<string, mixed> */
    private function pagePayload(MarketingPage $page): array
    {
        $sanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig)->allowSafeElements()->allowRelativeLinks()->allowRelativeMedias(),
        );
        $contentHtml = str_replace('{contact_email}', (string) config('marketing.contact_email'), $page->content_html);
        $path = match (true) {
            $page->page_type === 'article' => '/blog/'.$page->slug,
            $page->slug === 'home' => '/',
            $page->slug === 'blog' => '/blog',
            default => '/'.$page->slug,
        };

        return [
            'slug' => $page->slug,
            'type' => $page->page_type,
            'title' => $page->title,
            'excerpt' => $page->excerpt,
            'contentHtml' => $sanitizer->sanitize($contentHtml),
            'seoTitle' => $page->seo_title ?: $page->title,
            'seoDescription' => $page->seo_description ?: $page->excerpt,
            'canonicalUrl' => url($path),
            'publishedAt' => $page->published_at?->toDateString(),
        ];
    }

    private function sessionHasUsableInvitation(Request $request): bool
    {
        $token = $request->session()->get('early_access_invitation_token');

        if (! is_string($token) || $token === '') {
            return false;
        }

        $invitation = EarlyAccessInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        return $invitation?->isUsable() ?? false;
    }
}
