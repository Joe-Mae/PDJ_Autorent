<?php

namespace Tests\Feature;

use App\Services\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DocumentStorageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
    }

    public function test_an_image_is_reencoded_and_loses_its_exif_metadata(): void
    {
        $original = $this->jpegWithExif('GPS 6.1319,1.2228');
        $this->assertSame('GPS 6.1319,1.2228', exif_read_data($this->tempFile($original))['ImageDescription']);

        $stored = app(DocumentStorageService::class)->store(
            UploadedFile::fake()->createWithContent('cni.jpg', $original),
            'reservations/TEST',
        );

        $contents = Storage::disk('documents')->get($stored->path);
        $storedExif = @exif_read_data($this->tempFile($contents)) ?: [];

        $this->assertSame('image/jpeg', $stored->mimeType);
        $this->assertStringStartsWith('reservations/TEST/', $stored->path);
        $this->assertStringEndsWith('.jpg', $stored->path);
        $this->assertArrayNotHasKey('ImageDescription', $storedExif);
        $this->assertStringNotContainsString('GPS 6.1319', $contents);
        $this->assertStringNotContainsString("\xFF\xE1", $contents, 'Aucun segment APP1/EXIF ne doit subsister.');
    }

    public function test_the_stored_name_is_random_and_keeps_the_real_extension(): void
    {
        $stored = app(DocumentStorageService::class)->store(
            UploadedFile::fake()->image('Photo CNI ÉTÉ.jpg'),
            'reservations/TEST',
        );

        $this->assertStringNotContainsString('Photo', $stored->path);
        $this->assertMatchesRegularExpression('#/reservations/TEST/[A-Za-z0-9]{40}\.jpg$#', '/'.$stored->path);
        $this->assertStringEndsWith('.jpg', $stored->path);
        $this->assertSame('Photo CNI ÉTÉ.jpg', $stored->originalFilename);
    }

    public function test_a_pdf_is_stored_as_is(): void
    {
        $pdf = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF";

        $stored = app(DocumentStorageService::class)->store(
            UploadedFile::fake()->createWithContent('piece.pdf', $pdf),
            'reservations/TEST',
        );

        $this->assertSame('application/pdf', $stored->mimeType);
        $this->assertStringEndsWith('.pdf', $stored->path);
        $this->assertSame($pdf, Storage::disk('documents')->get($stored->path));
    }

    public function test_a_corrupted_image_is_rejected_with_a_validation_error(): void
    {
        $this->expectException(ValidationException::class);

        app(DocumentStorageService::class)->store(
            UploadedFile::fake()->createWithContent('cni.jpg', 'not an image at all'),
            'reservations/TEST',
            'identity_document',
        );
    }

    /** Petit JPEG valide, avec un segment APP1/EXIF greffé juste après l'en-tête. */
    private function jpegWithExif(string $description): string
    {
        $image = imagecreatetruecolor(24, 24);
        imagefilledrectangle($image, 0, 0, 23, 23, imagecolorallocate($image, 120, 160, 200));
        ob_start();
        imagejpeg($image, null, 90);
        $jpeg = (string) ob_get_clean();

        // IFD minimale : une entrée ASCII « ImageDescription ».
        $tiff = 'II'.pack('v', 42).pack('V', 8)
            .pack('v', 1)
            .pack('v', 0x010E).pack('v', 2).pack('V', strlen($description) + 1).pack('V', 26)
            .pack('V', 0)
            .$description."\0";
        $payload = "Exif\0\0".$tiff;
        $app1 = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

        return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pdj-exif-').'.jpg';
        file_put_contents($path, $contents);

        return $path;
    }
}
