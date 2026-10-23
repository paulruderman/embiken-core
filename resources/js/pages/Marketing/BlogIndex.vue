<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MarketingLayout from '@/components/MarketingLayout.vue';
import { show as articleRoute } from '@/routes/marketing/blog';
import { localRouteUrl } from '@/lib/local-route-url';

interface CmsPage {
    title: string;
    excerpt: string | null;
    contentHtml: string;
    seoTitle: string;
    seoDescription: string | null;
    canonicalUrl: string;
}

interface Article {
    slug: string;
    title: string;
    excerpt: string | null;
    publishedAt: string | null;
    canonicalUrl: string;
}

defineProps<{ page: CmsPage; articles: Article[] }>();
</script>

<template>
    <MarketingLayout>
        <Head :title="page.seoTitle">
            <meta v-if="page.seoDescription" name="description" :content="page.seoDescription" head-key="description" />
            <meta property="og:type" content="website" head-key="og:type" />
            <meta property="og:title" :content="page.seoTitle" head-key="og:title" />
            <meta v-if="page.seoDescription" property="og:description" :content="page.seoDescription" head-key="og:description" />
            <link rel="canonical" :href="page.canonicalUrl" head-key="canonical" />
        </Head>
        <main class="mx-auto max-w-7xl px-6 pt-14 pb-8 lg:px-10 lg:pt-20">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold tracking-[0.16em] text-emerald-800 uppercase">Practical notes for rental shops</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-[-0.045em] sm:text-6xl">{{ page.title }}</h1>
                <p v-if="page.excerpt" class="mt-5 text-lg leading-8 text-zinc-600">{{ page.excerpt }}</p>
                <div class="mt-6 text-sm leading-7 text-zinc-600" v-html="page.contentHtml" />
            </div>
            <section aria-label="Journal articles" class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="article in articles" :key="article.slug" class="flex flex-col rounded-[1.5rem] border border-zinc-200 bg-white p-6 shadow-sm">
                    <time v-if="article.publishedAt" :datetime="article.publishedAt" class="text-xs font-medium tracking-wide text-zinc-500 uppercase">{{ article.publishedAt }}</time>
                    <h2 class="mt-4 text-xl font-semibold tracking-tight">{{ article.title }}</h2>
                    <p class="mt-3 flex-1 text-sm leading-7 text-zinc-600">{{ article.excerpt }}</p>
                    <Link :href="localRouteUrl(articleRoute.url(article.slug))" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-emerald-900 hover:gap-3">
                        Read article <span aria-hidden="true">→</span>
                    </Link>
                </article>
                <p v-if="articles.length === 0" class="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-zinc-600">New articles are on the way.</p>
            </section>
        </main>
    </MarketingLayout>
</template>
