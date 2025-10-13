<?php

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Jobs\QueueExport;

beforeEach(function () {
    $this->user = User::factory()->create();
    Lead::factory()->count(2)->create();
    
    // Fake the storage disk
    Storage::fake('local');
});

test('export triggers export job', function () {
    Queue::fake();

    $this->actingAs($this->user)
        ->get(route('leads.export'))
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(QueueExport::class);
});

test('export with filters triggers export job', function () {
    Queue::fake();

    $this->actingAs($this->user)
        ->get(route('leads.export', [
            'search' => 'test',
            'status' => 'new'
        ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(QueueExport::class);
});

test('export requires authentication', function () {
    $this->get(route('leads.export'))
        ->assertRedirect(route('login'));
});

test('download export requires authentication', function () {
    $this->get(route('leads.downloadExport'))
        ->assertRedirect(route('login'));
});

test('download export returns file when available', function () {
    // Create a fake export file
    $fileName = 'temp_exports/leads-2024-01-01-120000-abc123.xlsx';
    
    // Create simple binary content that resembles Excel (ZIP header)
    $fakeExcelContent = hex2bin('504B0304140000000800');
    
    Storage::disk('local')->put($fileName, $fakeExcelContent);
    Cache::put("lead_export_{$this->user->id}", $fileName, now()->addMinutes(10));

    $response = $this->actingAs($this->user)
        ->get(route('leads.downloadExport'));

    $response->assertOk();
    $response->assertHeader('Content-Disposition');
});

test('download export returns 404 when file not in cache', function () {
    $response = $this->actingAs($this->user)
        ->get(route('leads.downloadExport'));

    $response->assertNotFound();
    
    $response->assertSee('404', false);
});

test('download export returns 404 when file not on disk', function () {
    $fileName = 'temp_exports/non-existent-file.xlsx';
    
    Cache::put("lead_export_{$this->user->id}", $fileName, now()->addMinutes(10));

    $response = $this->actingAs($this->user)
        ->get(route('leads.downloadExport'));

    $response->assertNotFound();
    $response->assertSee('404', false);
});

test('download export clears cache after successful download', function () {
    $fileName = 'temp_exports/leads-2024-01-01-120000-abc123.xlsx';
    $fakeExcelContent = hex2bin('504B0304140000000800'); // Simple ZIP header
    
    Storage::disk('local')->put($fileName, $fakeExcelContent);
    Cache::put("lead_export_{$this->user->id}", $fileName, now()->addMinutes(10));

    $this->actingAs($this->user)
        ->get(route('leads.downloadExport'))
        ->assertOk();

    // Cache should be cleared after download
    $this->assertFalse(Cache::has("lead_export_{$this->user->id}"));
});

test('download export handles different user caches separately', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $fileName1 = 'temp_exports/user1-export.xlsx';
    $fileName2 = 'temp_exports/user2-export.xlsx';
    
    Storage::disk('local')->put($fileName1, hex2bin('504B0304140000000800'));
    Storage::disk('local')->put($fileName2, hex2bin('504B0304140000000800'));
    
    Cache::put("lead_export_{$user1->id}", $fileName1, now()->addMinutes(10));
    Cache::put("lead_export_{$user2->id}", $fileName2, now()->addMinutes(10));

    // User1 should only see their file
    $this->actingAs($user1)
        ->get(route('leads.downloadExport'))
        ->assertOk();

    // User2's cache should still exist
    $this->assertTrue(Cache::has("lead_export_{$user2->id}"));
});

// Test for JSON response Inertia/API
test('download export returns json error for api requests', function () {
    $this->actingAs($this->user)
        ->get(route('leads.downloadExport'), [
            'Accept' => 'application/json',
        ])
        ->assertNotFound();
});