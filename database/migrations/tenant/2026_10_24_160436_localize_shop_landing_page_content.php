<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (DB::table('shop_landing_pages')->get() as $page) {
            $layoutData = json_decode($page->layout_data, true);
            $customContent = $page->custom_content === null ? null : json_decode($page->custom_content, true);
            $bookingNotices = json_decode($page->booking_notices, true);

            DB::table('shop_landing_pages')->where('id', $page->id)->update([
                'layout_data' => json_encode([
                    'shared' => array_intersect_key($layoutData, array_flip(['image_path', 'image_position'])),
                    'en' => array_diff_key($layoutData, array_flip(['image_path', 'image_position'])),
                ], JSON_THROW_ON_ERROR),
                'custom_content' => $customContent === null ? null : json_encode(['en' => $customContent], JSON_THROW_ON_ERROR),
                'booking_notices' => json_encode(array_map(static fn (array $notice): array => [
                    'tone' => $notice['tone'],
                    'message' => ['en' => $notice['message']],
                ], $bookingNotices ?? []), JSON_THROW_ON_ERROR),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (DB::table('shop_landing_pages')->get() as $page) {
            $layoutData = json_decode($page->layout_data, true);
            $customContent = $page->custom_content === null ? null : json_decode($page->custom_content, true);
            $bookingNotices = json_decode($page->booking_notices, true);

            DB::table('shop_landing_pages')->where('id', $page->id)->update([
                'layout_data' => json_encode([
                    ...($layoutData['en'] ?? []),
                    ...($layoutData['shared'] ?? []),
                ], JSON_THROW_ON_ERROR),
                'custom_content' => $customContent === null ? null : json_encode($customContent['en'] ?? null, JSON_THROW_ON_ERROR),
                'booking_notices' => json_encode(array_map(static fn (array $notice): array => [
                    'tone' => $notice['tone'],
                    'message' => $notice['message']['en'] ?? '',
                ], $bookingNotices ?? []), JSON_THROW_ON_ERROR),
            ]);
        }
    }
};
