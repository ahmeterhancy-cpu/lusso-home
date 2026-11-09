<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\XmlFeed;
use App\Services\FeedBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class XmlFeedController extends Controller
{
    public function __invoke(Request $request, string $slug, FeedBuilder $builder)
    {
        $feed = XmlFeed::where('slug', $slug)->where('is_active', true)->firstOrFail();
        abort_if($feed->token && ! hash_equals($feed->token, (string) $request->query('key')), 403);

        // Ürün ya da akış ayarı değişince önbellek kendiliğinden yenilenir; yoksa 30 dk tutulur.
        $stamp = $feed->updated_at?->timestamp.'-'.Product::max('updated_at');
        $xml = Cache::remember('xml-feed:'.$feed->id.':'.md5($stamp), now()->addMinutes(30), fn () => $builder->build($feed));

        XmlFeed::whereKey($feed->id)->update(['hits' => $feed->hits + 1, 'last_hit_at' => now()]);

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'X-Robots-Tag' => 'noindex']);
    }
}
