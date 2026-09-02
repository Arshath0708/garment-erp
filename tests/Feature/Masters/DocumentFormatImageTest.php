<?php

namespace Tests\Feature\Masters;

use App\Models\DocumentFormat;
use App\Models\DocumentFormatColumn;
use App\Models\User;
use App\Services\Masters\DocumentFormatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentFormatImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_update_saves_a_reference_image_when_none_existed_yet(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['status' => true]);
        $user->assignRole('Super Admin');

        $format = app(DocumentFormatService::class)->create([
            'name'                   => 'Kurta Format Images',
            'status'                 => 'active',
            'allow_multiple_colours' => true,
            'units'                  => ['PCS'],
            'columns'                => $this->columnPayload(),
            'column_order'           => array_keys(DocumentFormatColumn::STANDARD),
        ]);

        $payload = [
            'name'                   => $format->name,
            'status'                 => 'active',
            'allow_multiple_colours' => '1',
            'units'                  => ['PCS'],
            'columns'                => $this->columnPayload(),
            'column_order'           => array_keys(DocumentFormatColumn::STANDARD),
            'keep_images'            => [''],
            'images'                 => [UploadedFile::fake()->image('packing.jpg', 80, 80)],
        ];

        $this->actingAs($user)
            ->put(route('masters.formats.update', $format), $payload)
            ->assertRedirect(route('masters.formats.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $format->fresh()->images()->count());
    }

    /**
     * @return array<string, array{label: string, enabled: string}>
     */
    private function columnPayload(): array
    {
        $columns = [];

        foreach (DocumentFormatColumn::STANDARD as $key => $meta) {
            $columns[$key] = [
                'label'   => $meta['label'],
                'enabled' => '1',
            ];
        }

        return $columns;
    }
}
