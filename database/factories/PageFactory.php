<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Une page éditoriale bilingue. Le slug est dérivé du titre français pour
     * rester lisible dans l'URL.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleFr = fake()->unique()->sentence(3);

        return [
            'slug' => Str::slug($titleFr),
            'title_fr' => $titleFr,
            'title_en' => fake()->sentence(3),
            'content_fr' => '<p>'.fake()->paragraph().'</p>',
            'content_en' => '<p>'.fake()->paragraph().'</p>',
            'meta_description_fr' => fake()->sentence(12),
            'meta_description_en' => fake()->sentence(12),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
