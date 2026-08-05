<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoRestaurantSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $password = config('demo.owner_password') ?: Str::password(32);
            $owner = User::query()->updateOrCreate(
                ['email' => config('demo.owner_email')],
                ['name' => 'Bella Pasta Demo', 'password' => Hash::make($password)],
            );

            $logo = 'demo/bella-pasta-logo.webp';
            $cover = 'demo/bella-pasta-cover.webp';
            Storage::disk('public')->put($logo, file_get_contents(database_path('seeders/assets/bella-pasta-logo.webp')));
            Storage::disk('public')->put($cover, file_get_contents(database_path('seeders/assets/bella-pasta-cover.webp')));

            $restaurant = Restaurant::query()->updateOrCreate(['owner_id' => $owner->id], [
                'name' => 'Bella Pasta', 'name_ar' => 'بيلا باستا', 'name_en' => 'Bella Pasta', 'slug' => 'bella-pasta',
                'description' => 'Handmade Italian pasta, warm hospitality, and timeless flavors.',
                'description_ar' => 'باستا إيطالية مصنوعة يدوياً، وضيافة دافئة، ونكهات أصيلة.',
                'description_en' => 'Handmade Italian pasta, warm hospitality, and timeless flavors.',
                'default_language' => 'en', 'logo' => $logo, 'cover_image' => $cover, 'whatsapp' => '+970599000000',
                'phone' => '+97022900000', 'address' => 'Old City Promenade', 'currency' => 'ILS', 'primary_color' => '#A82424',
                'theme_key' => 'cafe', 'is_active' => true, 'opening_hours' => $this->openingHours(),
            ]);

            foreach ($this->menu() as $categoryData) {
                $items = $categoryData['items'];
                unset($categoryData['items']);
                $category = $restaurant->categories()->updateOrCreate(['name_en' => $categoryData['name_en']], $categoryData);
                foreach ($items as $position => $item) {
                    $restaurant->menuItems()->updateOrCreate(['category_id' => $category->id, 'name_en' => $item['name_en']], $item + ['category_id' => $category->id, 'sort_order' => $position + 1]);
                }
            }
        });
    }

    private function openingHours(): array
    {
        return collect(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])->mapWithKeys(fn (string $day) => [$day => $day === 'friday' ? ['is_open' => false, 'open' => null, 'close' => null] : ['is_open' => true, 'open' => '11:00', 'close' => '23:00']])->all();
    }

    private function menu(): array
    {
        return [
            ['name' => 'Fresh Pasta', 'name_ar' => 'باستا طازجة', 'name_en' => 'Fresh Pasta', 'sort_order' => 1, 'is_active' => true, 'items' => [
                ['name' => 'Tagliatelle al Pomodoro', 'name_ar' => 'تالياتيلي بصلصة الطماطم', 'name_en' => 'Tagliatelle al Pomodoro', 'description' => 'Hand-cut pasta, San Marzano tomatoes, basil, and aged parmesan.', 'description_ar' => 'باستا مقطعة يدوياً مع طماطم سان مارزانو والريحان والبارميزان المعتق.', 'description_en' => 'Hand-cut pasta, San Marzano tomatoes, basil, and aged parmesan.', 'price' => 42, 'is_available' => true, 'is_featured' => true],
                ['name' => 'Truffle Fettuccine', 'name_ar' => 'فيتوتشيني بالكمأة', 'name_en' => 'Truffle Fettuccine', 'description' => 'Creamy wild mushroom sauce, truffle, and pecorino.', 'description_ar' => 'صلصة فطر بري كريمية مع الكمأة وجبن البيكورينو.', 'description_en' => 'Creamy wild mushroom sauce, truffle, and pecorino.', 'price' => 58, 'is_available' => true, 'is_featured' => true],
            ]],
            ['name' => 'Stone-Baked Pizza', 'name_ar' => 'بيتزا مخبوزة على الحجر', 'name_en' => 'Stone-Baked Pizza', 'sort_order' => 2, 'is_active' => true, 'items' => [
                ['name' => 'Margherita Classica', 'name_ar' => 'مارغريتا كلاسيكية', 'name_en' => 'Margherita Classica', 'description' => 'Tomato, fior di latte, basil, and extra virgin olive oil.', 'description_ar' => 'طماطم وجبن فيور دي لاتيه وريحان وزيت زيتون بكر.', 'description_en' => 'Tomato, fior di latte, basil, and extra virgin olive oil.', 'price' => 38, 'is_available' => true, 'is_featured' => false],
                ['name' => 'Burrata & Pesto', 'name_ar' => 'بوراتا وبيستو', 'name_en' => 'Burrata & Pesto', 'description' => 'Burrata, roasted tomatoes, basil pesto, and toasted pine nuts.', 'description_ar' => 'جبن بوراتا وطماطم مشوية وبيستو الريحان وصنوبر محمص.', 'description_en' => 'Burrata, roasted tomatoes, basil pesto, and toasted pine nuts.', 'price' => 54, 'is_available' => true, 'is_featured' => true],
            ]],
            ['name' => 'Dolci', 'name_ar' => 'حلويات', 'name_en' => 'Dolci', 'sort_order' => 3, 'is_active' => true, 'items' => [
                ['name' => 'Classic Tiramisu', 'name_ar' => 'تيراميسو كلاسيكي', 'name_en' => 'Classic Tiramisu', 'description' => 'Espresso-soaked ladyfingers, mascarpone, and cocoa.', 'description_ar' => 'بسكويت منقوع بالإسبريسو مع ماسكاربوني وكاكاو.', 'description_en' => 'Espresso-soaked ladyfingers, mascarpone, and cocoa.', 'price' => 28, 'is_available' => true, 'is_featured' => false],
            ]],
        ];
    }
}
