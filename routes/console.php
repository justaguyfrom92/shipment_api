<?php

use App\Services\ShipmentExportService;
use App\Services\GitHubService;
use Illuminate\Support\Facades\Schedule;

Artisan::command('daily:upload {--message=Daily automated commit}', function (ShipmentExportService $exportService, GitHubService $gitHubService)
{
	$this->info('Starting daily upload to GitHub...');

	try
	{
		$this->info('Exporting today\'s shipment data...');
		$exportResult = $exportService->exportTodaysShipments();

		if ($exportResult['success'])
		{
			$this->info("✓ Exported to: {$exportResult['filename']}");
			$this->info("✓ Total shipments exported: {$exportResult['count']}");
		}
		else
		{
			$this->warn("⚠ {$exportResult['message']}");
		}

		$commitMessage = $this->option('message') . ' - ' . now()->format('Y-m-d H:i:s');
		$this->info('Uploading to GitHub...');

		$uploadResult = $gitHubService->uploadToGitHub($commitMessage);

		if ($uploadResult['success'])
		{
			$this->info('✓ ' . $uploadResult['message']);
			return 0;
		}
		else
		{
			$this->error('✗ ' . $uploadResult['message']);
			if (isset($uploadResult['error']))
			{
				$this->error($uploadResult['error']);
			}
			return 1;
		}
	}
	catch (\Exception $e)
	{
		$this->error('Error during upload: ' . $e->getMessage());
		return 1;
	}
})->purpose('Export today\'s shipments and upload to GitHub')->daily();
