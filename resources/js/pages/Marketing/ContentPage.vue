<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MarketingLayout from '@/components/MarketingLayout.vue';
import { blog } from '@/routes/marketing';
import { localRouteUrl } from '@/lib/local-route-url';

interface MarketingPage {
    slug: string;
    type: 'page' | 'article';
    title: string;
    excerpt: string | null;
    contentHtml: string;
    seoTitle: string;
    seoDescription: string | null;
    canonicalUrl: string;
    publishedAt: string | null;
    isArticle: boolean;
}

defineProps<{ page: MarketingPage }>();
</script>

<template>
    <MarketingLayout>
        <Head :title="page.seoTitle">
            <meta v-if="page.seoDescription" name="description" :content="page.seoDescription" head-key="description" />
            <meta property="og:type" :content="page.isArticle ? 'article' : 'website'" head-key="og:type" />
            <meta property="og:title" :content="page.seoTitle" head-key="og:title" />
            <meta v-if="page.seoDescription" property="og:description" :content="page.seoDescription" head-key="og:description" />
            <link rel="canonical" :href="page.canonicalUrl" head-key="canonical" />
        </Head>

        <main class="mx-auto max-w-5xl px-6 pt-14 pb-4 lg:px-10 lg:pt-20">
            <div v-if="page.isArticle" class="mb-8 flex items-center gap-3 text-sm text-zinc-500">
                <Link :href="localRouteUrl(blog())" class="font-medium text-emerald-900 hover:underline">Journal</Link>
                <span aria-hidden="true">/</span>
                <time v-if="page.publishedAt" :datetime="page.publishedAt">{{ page.publishedAt }}</time>
            </div>
            <div class="max-w-3xl">
                <p class="inline-flex rounded-full border border-emerald-900/15 bg-white/80 px-3 py-1.5 text-xs font-semibold tracking-wide text-emerald-950 uppercase">
                    {{ page.isArticle ? 'Embiken journal' : 'Embiken early access' }}
                </p>
                <h1 class="mt-5 text-4xl font-semibold tracking-[-0.045em] sm:text-6xl">{{ page.title }}</h1>
                <p v-if="page.excerpt" class="mt-5 max-w-2xl text-lg leading-8 text-zinc-600">{{ page.excerpt }}</p>
            </div>
            <article class="marketing-copy mt-12 max-w-3xl rounded-[1.75rem] border border-zinc-200 bg-white p-6 shadow-sm sm:p-10" v-html="page.contentHtml" />
        </main>
    </MarketingLayout>
</template>

<style scoped>
.marketing-copy :deep(h2) { margin-top: 2.25rem; margin-bottom: 0.75rem; font-size: 1.5rem; line-height: 2rem; font-weight: 650; letter-spacing: -0.025em; }
.marketing-copy :deep(h3) { margin-top: 1.75rem; margin-bottom: 0.5rem; font-size: 1.15rem; line-height: 1.7rem; font-weight: 650; }
.marketing-copy :deep(p) { margin-top: 0.9rem; line-height: 1.8; color: #52525b; }
.marketing-copy :deep(ul), .marketing-copy :deep(ol) { margin-top: 0.9rem; padding-left: 1.4rem; line-height: 1.8; color: #52525b; }
.marketing-copy :deep(ul) { list-style: disc; }
.marketing-copy :deep(ol) { list-style: decimal; }
.marketing-copy :deep(li + li) { margin-top: 0.35rem; }
.marketing-copy :deep(a) { color: #065f46; font-weight: 600; text-decoration: underline; text-underline-offset: 3px; }
.marketing-copy :deep(blockquote) { margin-top: 1.5rem; border-left: 3px solid #a3e635; padding-left: 1rem; color: #3f3f46; }
.marketing-copy :deep(hr) { margin-top: 2rem; margin-bottom: 2rem; border-color: #e4e4e7; }
</style>
