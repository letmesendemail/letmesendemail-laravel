<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

test('config can be published', function () {
    $targetPath = config_path('letmesendemail.php');

    if (File::exists($targetPath)) {
        File::delete($targetPath);
    }

    $this->artisan('vendor:publish', ['--tag' => 'letmesendemail-config'])->assertExitCode(0);

    expect(File::exists($targetPath))->toBeTrue();

    $published = include $targetPath;
    expect($published)->toHaveKey('api_key');
    expect($published)->toHaveKey('base_url');
    expect($published)->toHaveKey('timeout');
    expect($published)->toHaveKey('retries');
    expect($published)->toHaveKey('webhooks');

    File::delete($targetPath);
});
