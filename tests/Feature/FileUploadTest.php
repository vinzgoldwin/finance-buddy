<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function user_can_upload_pdf_file()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        
        $response = $this->post('/files/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('status');
    }

    /** @test */
    public function user_can_upload_csv_file()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('transactions.csv', "date,description,amount\n2023-01-01,Test Transaction,100.00");
        
        $response = $this->post('/files/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('status');
    }

    /** @test */
    public function user_can_upload_excel_file()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('transactions.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        
        $response = $this->post('/files/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('status');
    }

    /** @test */
    public function file_must_be_valid_type()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');
        
        $response = $this->post('/files/upload', [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }
}
