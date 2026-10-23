<?php

namespace Database\Seeders;

use App\Models\MarketingPage;
use Illuminate\Database\Seeder;

class MarketingPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MarketingPage::query()->where('slug', 'home')->delete();

        $pages = [
            [
                'slug' => 'features', 'page_type' => 'page', 'sort_order' => 2,
                'title' => 'A clear start for your rental shop',
                'excerpt' => 'Embiken is in early access. Today, you can create a shop workspace and review the plan and setup details before getting started.',
                'seo_title' => 'What is available in Embiken early access',
                'seo_description' => 'See what Embiken supports today: shop workspace setup, plan selection, timezone and currency setup, and a Manager invitation.',
                'content_html' => '<h2>Available today</h2><p>The public site supports plan comparison and shop signup. Signup creates a shop workspace on its own subdomain, sets up its first location with the timezone and currency you choose, and sends the Manager an invitation to set a password.</p><p>You can choose Starter, Standard, Pro, or Enterprise Custom. Signup does not collect a subscription payment because billing checkout is not connected yet. Embiken-processed payment rates are shown on the pricing page; live payment collection is not enabled through this signup flow.</p><h2>Rental and shop operations</h2><p>Rental, fleet management, and workshop screens are still in development and should not yet be treated as ready for day-to-day operations. We are keeping plan capability limits soft while the product takes shape.</p><p>If you would like to discuss early access or tell us what your shop needs, <a href="/contact">get in touch</a>.</p>',
                'published_at' => now(),
            ],
            [
                'slug' => 'rentals', 'page_type' => 'page', 'sort_order' => 3,
                'title' => 'Rental operations for bike shops',
                'excerpt' => 'Rental workflows are in development. Embiken is currently in early access and is not yet ready to run a shop’s daily rental operation.',
                'seo_title' => 'Bike rental operations software | Embiken early access',
                'seo_description' => 'Learn about Embiken’s early-access status for bike rental operations and contact us to discuss current availability.',
                'content_html' => '<h2>Current availability</h2><p>Embiken’s rental tools are being developed for independent bike rental shops. The public plan comparison and shop workspace signup are available today, but the rental workflow is not yet ready to manage live shop operations.</p><p>We are keeping plan capabilities and limits flexible while the product develops. If rental operations are your main requirement, contact us first so we can be clear about current access and fit.</p><p><a href="/contact">Ask about rental tools and early access</a>.</p>',
                'published_at' => now(),
            ],
            [
                'slug' => 'fleet-management', 'page_type' => 'page', 'sort_order' => 4,
                'title' => 'Fleet management for independent shops',
                'excerpt' => 'Fleet management tools are in development. Embiken is in early access; the shop workspace setup flow is available today.',
                'seo_title' => 'Bike fleet management software | Embiken early access',
                'seo_description' => 'Embiken is developing fleet management software for independent bike rental shops. See current early-access availability.',
                'content_html' => '<h2>Designed for real-world rental fleets</h2><p>Rental fleets range from a few bikes stored at home to large seasonal collections. Embiken is being built for shops that want a practical place to organize their operation as it grows.</p><h2>Current availability</h2><p>Fleet management screens are still in development and are not yet ready for day-to-day fleet tracking. Today, you can create a shop workspace, choose the shop’s timezone and currency, and invite its Manager.</p><p><a href="/contact">Contact us about fleet management early access</a>.</p>',
                'published_at' => now(),
            ],
            [
                'slug' => 'workshop', 'page_type' => 'page', 'sort_order' => 5,
                'title' => 'Workshop tools for rental teams',
                'excerpt' => 'Workshop tools are in development. Embiken is in early access, with shop workspace setup and pricing information available today.',
                'seo_title' => 'Bike rental workshop software | Embiken early access',
                'seo_description' => 'Embiken is developing workshop tools for independent bike rental shops. Contact us to discuss current early-access availability.',
                'content_html' => '<h2>Keep maintenance in the picture</h2><p>Rental bikes need regular attention, and the workshop is part of the rental operation. Embiken is being developed with that connection in mind.</p><h2>Current availability</h2><p>Workshop management screens are still in development. The current public site supports shop signup, workspace setup, and plan comparison; it does not yet provide a complete workshop workflow.</p><p><a href="/contact">Tell us what your workshop needs</a>.</p>',
                'published_at' => now(),
            ],
            [
                'slug' => 'pricing', 'page_type' => 'page', 'sort_order' => 6,
                'title' => 'Simple plans, clear payment rates.',
                'excerpt' => 'Start with no subscription fee. A single combined processing rate applies only to payments Embiken processes for your shop.',
                'seo_title' => 'Embiken plans and pricing for bike rental shops',
                'seo_description' => 'Compare Embiken Starter, Standard, Pro, and Enterprise pricing. See subscription prices and combined rates for payments processed by Embiken.',
                'content_html' => '<h2>What the payment rate means</h2><p>The displayed percentage is one combined rate for payments processed by Embiken. It includes Stripe’s processing charges and Embiken’s share. Cash, other payment methods, and payments taken through another provider do not incur the Embiken processing percentage when you simply record them in the software.</p><h2>Early-access billing</h2><p>Plan prices and payment rates are shown so you can compare options. Subscription checkout is not connected yet, and signup does not collect a subscription payment. Plan capability limits are still being defined.</p><p>For a larger fleet or a custom arrangement, <a href="/contact">contact us about Enterprise</a>.</p>',
                'published_at' => now(),
            ],
            [
                'slug' => 'faq', 'page_type' => 'page', 'sort_order' => 7,
                'title' => 'Questions, answered.',
                'excerpt' => 'The practical details about early access, plan pricing, shop setup, and payment processing.',
                'seo_title' => 'Embiken frequently asked questions',
                'seo_description' => 'Answers about Embiken early access, free Starter plan, payment rates, signup, subscriptions, supported locations, and availability.',
                'content_html' => '<h2>Is Embiken ready to run my shop?</h2><p>Embiken is in early access. Shop workspace setup and plan comparison are available; rental, fleet management, and workshop screens are still in development. Please contact us before relying on Embiken for daily operations.</p><h2>Is Starter really free?</h2><p>Starter has no subscription fee and is intended to remain free indefinitely. If Embiken processes a customer payment for your shop, the Starter combined processing rate applies. Plan features and limits are still being defined.</p><h2>What does the combined payment rate include?</h2><p>It is the single rate shown for payments processed by Embiken, combining Stripe processing charges and Embiken’s share. Embiken absorbs differences in the underlying processing costs.</p><h2>Can I use another payment provider?</h2><p>Yes. A shop may take payment elsewhere and record it in Embiken. The Embiken processing percentage applies only to payments Embiken processes.</p><h2>Does signup collect a subscription payment?</h2><p>No. Signup creates a shop workspace and sends the Manager invitation. Subscription checkout is not connected yet, so no subscription charge is collected during signup.</p><h2>How many shop locations are supported?</h2><p>The first release is structured around one location per shop workspace. Multi-location support is reserved for a higher tier or Enterprise and is not available in the current release.</p><h2>Can I take card payments in person?</h2><p>The combined rate is intended to cover payments processed by Embiken, including future in-person card payments through Stripe Terminal. In-person Terminal payments are not available yet.</p><h2>Which currencies and languages are planned?</h2><p>The initial target markets are the United States, Canada, Spain, and the United Kingdom, with English, Canadian French, and Spanish and USD, CAD, EUR, and GBP in view. International setup is still being prepared; contact us if you need a specific market.</p><h2>How do I ask about Enterprise?</h2><p><a href="/contact">Contact the Embiken team</a> to discuss a custom subscription price, processing rate, fleet needs, or support.</p>',
                'published_at' => now(),
            ],
            [
                'slug' => 'contact', 'page_type' => 'page', 'sort_order' => 8,
                'title' => 'Let’s talk about your shop.',
                'excerpt' => 'Ask about Enterprise, early access, or whether Embiken is a fit for your bike rental operation.',
                'seo_title' => 'Contact Embiken',
                'seo_description' => 'Contact Embiken to discuss early access, Enterprise pricing, fleet needs, and support for your bike rental shop.',
                'content_html' => '<h2>Tell us what you need</h2><p>Embiken is in early access. We would like to understand your shop, your fleet, and the parts of the rental day you want to make easier. We can also discuss Enterprise pricing and current product availability.</p><p>Email us at <a href="mailto:{contact_email}">{contact_email}</a>. Including your shop name, location, approximate fleet size, and the tools you need will help us respond with useful information.</p><h2>Plan questions</h2><p>Starter has no subscription fee. The displayed combined payment rate applies only when Embiken processes a customer payment. Subscription checkout is not connected yet, so no subscription charge is collected during signup.</p>',
                'published_at' => now(),
            ],
            [
                'slug' => 'terms', 'page_type' => 'page', 'sort_order' => 9,
                'title' => 'Terms of service',
                'excerpt' => 'Terms for the Embiken website and early-access shop workspace.',
                'seo_title' => 'Embiken terms of service',
                'seo_description' => 'Terms covering use of the Embiken website, shop signup, early-access software, payment processing, and account responsibilities.',
                'content_html' => '<p><strong>Draft for review. These terms should be reviewed by qualified counsel and completed with the service operator’s legal identity and notice address before public launch.</strong></p><h2>About these terms</h2><p>These terms govern access to the Embiken website and shop workspace service (the “Service”). By creating a shop workspace or using the Service, you agree to these terms on behalf of the shop or business you represent. “Embiken,” “we,” and “us” refer to the operator of the Service.</p><h2>Early access</h2><p>The Service is in early access. Features may be incomplete, changed, interrupted, or unavailable. Rental, fleet management, and workshop screens are still in development. Do not rely on the current Service as the only record of a rental, payment, customer, or safety-critical maintenance decision.</p><h2>Shop accounts</h2><p>You must provide accurate signup details, be authorized to represent the shop, and keep account access secure. You are responsible for activity performed through the shop workspace and for the accuracy of shop, customer, rental, and payment information entered there.</p><h2>Plans and charges</h2><p>Plan subscription prices and combined payment rates are displayed on the pricing page and may be updated. Starter currently has no subscription fee. Subscription billing checkout is not connected, and signup does not collect a subscription payment. If Embiken processes a payment for your shop, the selected plan’s displayed combined rate applies; that rate includes Stripe processing charges and Embiken’s share. Payments processed outside Embiken and merely recorded in the Service are not subject to that percentage. Any future subscription billing terms will be shown before a subscription payment is collected.</p><h2>Payment services</h2><p>Where available, payment processing is provided through Stripe Connect and is subject to Stripe’s terms and onboarding requirements. Shops remain responsible for their customer-facing rental terms, taxes, refunds, and legal obligations. Embiken does not determine whether a shop should accept or refund a rental.</p><h2>Acceptable use and suspension</h2><p>You may not misuse the Service, attempt unauthorized access, interfere with its operation, or use it unlawfully. We may restrict or suspend access when reasonably needed to protect the Service, its users, or the public, or to address a breach of these terms.</p><h2>Shop data</h2><p>You retain responsibility for the shop data you provide and must have the rights and permissions needed to use it. You may contact us with questions about access, export, or deletion. Early-access retention and deletion procedures may change as the Service develops.</p><h2>Availability and liability</h2><p>The Service is provided during early access and may be unavailable or contain errors. To the extent permitted by law, the Service is provided without warranties, and Embiken is not liable for indirect or consequential losses. Nothing in these terms limits rights or liabilities that cannot lawfully be excluded.</p><h2>Changes and contact</h2><p>We may update these terms as the Service changes. We will post the updated terms here and revise the effective date. Contact <a href="mailto:{contact_email}">{contact_email}</a> with questions.</p>',
                'published_at' => now(),
            ],
            [
                'slug' => 'privacy', 'page_type' => 'page', 'sort_order' => 10,
                'title' => 'Privacy notice',
                'excerpt' => 'How Embiken handles information when you visit the site or create a shop workspace.',
                'seo_title' => 'Embiken privacy notice',
                'seo_description' => 'Learn what information Embiken handles for website visitors, shop managers, and rental customers during early access.',
                'content_html' => '<p><strong>Draft for review. Before public launch, complete the service operator’s legal identity and notice address, and review this notice with qualified privacy counsel for each target market.</strong></p><h2>Who this notice covers</h2><p>This notice describes information handled by Embiken when you visit the public website, contact us, or create and use a shop workspace. A shop operator is responsible for its own privacy notices and lawful use of customer information entered into the Service.</p><h2>Information we handle</h2><p>For shop signup, we handle the shop name, requested shop web address, manager name and email, and setup choices such as timezone and currency. We also handle technical information needed to operate and secure the website and shop workspace. If the Service is used for rentals, shop users may enter renter names, email addresses, phone numbers, reservation details, and payment records.</p><h2>Payments</h2><p>Where payment processing is available, Stripe Connect handles payment onboarding and payment details. Embiken does not ask shops to enter card numbers into this website. Stripe’s own privacy terms also apply to its services.</p><h2>How information is used</h2><ul><li>To create and operate shop workspaces and send account invitations.</li><li>To provide requested support and respond to inquiries.</li><li>To secure, maintain, and improve the Service.</li><li>To meet legal obligations and protect the Service and its users.</li></ul><h2>Sharing and service providers</h2><p>We share information with service providers needed to host, secure, and operate the Service, including Stripe when payment services are used. We do not sell personal information. We may disclose information when required by law or when necessary to protect rights, safety, and security.</p><h2>Retention and deletion</h2><p>We keep information for as long as needed to operate the Service, meet legal obligations, resolve disputes, and protect the platform. A shop workspace may be suspended or archived. Contact us to ask about access, correction, or deletion; applicable rights depend on where you live. We will explain the process and any limits that apply.</p><h2>Security and international transfers</h2><p>We use safeguards intended to protect information, but no online service can guarantee absolute security. Information may be processed in countries where Embiken or its providers operate, subject to applicable legal requirements.</p><h2>Cookies and analytics</h2><p>The website may use essential technologies needed for sessions, security, and application functionality. If non-essential analytics or advertising technologies are added, this notice and any required consent controls should be updated before use.</p><h2>Children and changes</h2><p>The Service is for businesses and is not designed for children. We may update this notice as the Service changes. Contact <a href="mailto:{contact_email}">{contact_email}</a> with privacy questions.</p>',
                'published_at' => now(),
            ],
            [
                'slug' => 'blog', 'page_type' => 'page', 'sort_order' => 11,
                'title' => 'Notes for bike rental shops',
                'excerpt' => 'Practical ideas for organizing rental operations, keeping a fleet ready, and making busy days easier.',
                'seo_title' => 'Bike rental shop journal | Embiken',
                'seo_description' => 'Practical articles for independent bike rental businesses, from fleet organization to preparing for a busy rental season.',
                'content_html' => '<p>Useful, practical guidance for the people who keep rental bikes moving. Embiken is in early access; these articles share general shop practices and do not describe features currently available in the software.</p>',
                'published_at' => now(),
            ],
            [
                'slug' => 'bike-rental-fleet-inventory-checklist', 'page_type' => 'article', 'sort_order' => 1,
                'title' => 'A simple inventory checklist for a bike rental fleet',
                'excerpt' => 'A reliable fleet list starts with a few consistent identifiers and a routine for keeping records current.',
                'seo_title' => 'Bike rental fleet inventory checklist',
                'seo_description' => 'Build a practical bike rental inventory with unique identifiers, condition notes, maintenance history, and a repeatable update routine.',
                'content_html' => '<p>A fleet inventory is useful only when staff can trust it. A simple, consistently maintained list can help a small operation answer basic questions: which bike is this, is it ready to rent, and what should happen next?</p><h2>Give every bike a shop identifier</h2><p>Choose a short code that is easy to see and say at the counter. Use one code per physical bike and keep it stable, even when the bike changes category or gets a new accessory. Record the manufacturer and model separately from your own identifier.</p><h2>Record the details people actually use</h2><p>Start with make, model, size, bike type, and any fit notes that affect a rental. Add purchase date and serial information if those help your maintenance or insurance records. Keep the list focused enough that staff will update it.</p><h2>Separate readiness from maintenance notes</h2><p>Use a clear ready or unavailable status, then keep a short note explaining why an unavailable bike is parked. A status answers “can we rent it now?” while the note gives the next person context. Avoid relying on a long freeform note as the only signal.</p><h2>Set a repeatable check-in routine</h2><p>Choose when the inventory is reviewed: after returns, before opening, or during scheduled maintenance. Assign ownership for the update so repairs and bike moves do not live only in someone’s memory. A quick weekly review can catch missing bikes, stale statuses, and incomplete notes.</p><h2>Keep the process proportional</h2><p>A garage operation may need a spreadsheet and a visible tag. A larger fleet may need roles, filters, and a more structured tool. Start with the identifiers and checks your team can maintain today, then add detail when it answers a real operational question.</p>',
                'published_at' => now()->subDays(21),
            ],
            [
                'slug' => 'prepare-bike-rental-shop-busy-season', 'page_type' => 'article', 'sort_order' => 2,
                'title' => 'Prepare a bike rental shop for a busy season',
                'excerpt' => 'A practical pre-season review of fleet readiness, staffing, opening hours, and the handoff at the counter.',
                'seo_title' => 'How to prepare a bike rental shop for peak season',
                'seo_description' => 'Plan for peak bike rental season with a fleet review, maintenance schedule, staff handoffs, customer information, and a simple daily checklist.',
                'content_html' => '<p>Peak season puts pressure on every part of a rental operation. A short, early review gives a shop time to find gaps before the first sunny weekend turns into a queue.</p><h2>Review the fleet before demand arrives</h2><p>Walk every bike, confirm its size and fit information, and separate ready bikes from anything that needs attention. Check common wear items and make a plan for parts that are difficult to source. Keep a small reserve for bikes that unexpectedly need repair.</p><h2>Make the rental handoff repeatable</h2><p>Agree on the essentials staff cover at pickup: customer contact details, bike assignment, fit and safety checks, return time, and what to do if plans change. A short shared checklist helps seasonal or part-time staff give consistent instructions.</p><h2>Check hours and capacity</h2><p>Compare opening hours with the times customers typically want to collect and return bikes. Identify the days when your team will be stretched, then decide which tasks can happen before opening and which need a named owner during the shift.</p><h2>Prepare for common exceptions</h2><p>Decide how staff will handle a late return, a bike that comes back with a problem, a customer who needs a size change, and a payment question. Clear escalation paths reduce on-the-spot guesswork.</p><h2>Debrief while the details are fresh</h2><p>After the first busy weeks, ask staff where time is lost and which information they have to look up repeatedly. Use that feedback to adjust the checklist and schedule. Small, specific changes usually help more than a complex process no one follows.</p>',
                'published_at' => now()->subDays(14),
            ],
            [
                'slug' => 'choose-bike-rental-turnaround-time', 'page_type' => 'article', 'sort_order' => 3,
                'title' => 'How to plan bike rental turnaround time',
                'excerpt' => 'A return time is only one part of getting a bike ready for the next rider. Build realistic time for inspection and reset.',
                'seo_title' => 'Planning bike rental turnaround time',
                'seo_description' => 'Learn how to plan bike rental turnaround time around returns, inspection, cleaning, maintenance checks, and shop capacity.',
                'content_html' => '<p>Two rentals can fit back-to-back on a calendar and still be impossible to serve well. A shop needs time to bring a bike in, inspect it, handle a quick reset, and prepare it for the next rider.</p><h2>Map the return-to-ready steps</h2><p>List what happens after a bike comes back: confirm the return, check for visible damage, clean or recharge it, note work needed, and move it to the ready area. Some rentals need only a short check; others need more time because of the bike type, weather, or rider use.</p><h2>Use real observations</h2><p>Estimate the time for each step, then compare the estimate with several actual returns. Track the difference between a routine reset and a return that needs maintenance. One average can hide the cases that most often create a bottleneck.</p><h2>Include your staffing pattern</h2><p>Turnaround is affected by who is working and what else they must do. A bike returned at lunch may wait longer than one returned near closing. Match the schedule to actual staff coverage instead of assuming every minute is available for resets.</p><h2>Leave room for exceptions</h2><p>Flat tires, missing equipment, and late arrivals happen. If every slot assumes the fastest possible turnaround, one small problem can disrupt the rest of the day. A modest buffer protects the customer experience and gives staff room to make a safe decision.</p><h2>Review the plan during the season</h2><p>Check your assumptions after a few weeks and after major changes to fleet size or rental duration. A useful turnaround policy should reflect the bikes and people you have, not a fixed rule copied from another shop.</p>',
                'published_at' => now()->subDays(7),
            ],
        ];

        foreach ($pages as $page) {
            MarketingPage::query()->firstOrCreate(['slug' => $page['slug']], $page);
        }
    }
}
