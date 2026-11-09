<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Site iskeleti: yönetici, içerik sayfaları, slider, ayarlar, menü sırası.
 * Ürün ve kategoriler `php artisan lusso:aktar` ile eski siteden gelir.
 * Tekrar çalıştırılabilir; panelden yapılan değişikliklerin üzerine yazmaz.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@lussohomeconcept.com');
        if (! User::where('email', $email)->exists()) {
            $password = env('ADMIN_PASSWORD') ?: Str::password(14, symbols: false);
            $user = User::create(['name' => 'Lusso Yönetici', 'email' => $email, 'password' => $password]);
            $user->forceFill(['is_admin' => true])->save();
            $this->command?->warn("Yönetici: {$email} / {$password}  (ilk girişte değiştirin)");
        }

        foreach (require database_path('data/pages.php') as $slug => $p) {
            Page::firstOrCreate(['slug' => $slug], ['title' => $p['title'], 'content' => $p['content']]);
        }

        if (Slide::count() === 0) {
            File::ensureDirectoryExists(public_path('medya/slider'));
            $slides = [
                ['#MyLussoLife', 'LUSSO HOME CONCEPT'],
                ['Lusso Home Concept', '#MyLussoLife'],
                ['#MyLussoLife', 'LUSSO HOME CONCEPT'],
            ];
            foreach ($slides as $i => [$kicker, $title]) {
                $n = $i + 1;
                if (is_file(public_path("img/slide-$n.jpg"))) {
                    File::copy(public_path("img/slide-$n.jpg"), public_path("medya/slider/slide-$n.jpg"));
                }
                Slide::create(['image' => "slider/slide-$n.jpg", 'kicker' => $kicker, 'title' => $title, 'show_logo' => true, 'sort' => $n]);
            }
        }

        // Eski sitedeki menü sırası ve "Trend Kategoriler" (sıra + görsel).
        $menu = ['aksesuar', 'sofra-grubu', 'tekstil', 'kitap', 'mackenzie-childs', 'outlet'];
        $trend = ['mackenzie-childs' => 1, 'kitap' => 2, 'sofra-grubu' => 3, 'tekstil' => 4, 'aksesuar' => 5];
        File::ensureDirectoryExists(public_path('medya/kategoriler'));

        Category::whereNull('parent_id')->whereNotIn('slug', $menu)->update(['in_menu' => false]);
        foreach ($menu as $pos => $slug) {
            $cat = Category::where('slug', $slug)->first();
            if (! $cat) {
                continue;
            }
            $data = ['sort' => $pos, 'in_menu' => true, 'parent_id' => null];
            if (isset($trend[$slug]) && ! $cat->is_trend) {
                $n = $trend[$slug];
                File::copy(public_path("img/trend-$n.jpg"), public_path("medya/kategoriler/trend-$n.jpg"));
                $data += ['is_trend' => true, 'trend_sort' => $n, 'image' => "kategoriler/trend-$n.jpg"];
            }
            $cat->update($data);
        }

        $defaults = [
            'site_name' => 'Lusso Home Concept',
            'support_phone' => '+90 539 109 99 00',
            'whatsapp' => '905391099900',
            'email' => 'info@lussohomeconcept.com',
            'facebook' => 'https://www.facebook.com/lussohomeconcept',
            'instagram' => 'https://www.instagram.com/lussohomeconcept',
            'free_shipping_min' => '5000',
            'shipping_fee' => '0',
            'per_page' => '12',
            'home_grid_category' => 'sofra-grubu',
            'pay_tiko' => '1',
            'pay_havale' => '1',
            'pay_kapida' => '1',
            'tiko_test_mode' => '1',
            'copyright_year' => '2021',
        ];
        foreach ($defaults as $k => $v) {
            Setting::firstOrCreate(['key' => $k], ['value' => $v]);
        }
        Cache::forget('settings');
        Cache::forget('menu-tree');
    }
}
