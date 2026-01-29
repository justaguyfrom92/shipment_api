<?php

namespace App\Console\Commands;

use App\Services\ShipmentExportService;
use App\Services\GitHubService;
use Illuminate\Console\Command;

class DailyUploadCommand extends Command
{
	protected $signature = 'daily:upload {--message=Daily automated commit}';
	protected $description = 'Export today\'s shipments and upload to GitHub';

	public function handle(ShipmentExportService $exportService, GitHubService $gitHubService): int
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
				return self::SUCCESS;
			}
			else
			{
				$this->error('✗ ' . $uploadResult['message']);
				if (isset($uploadResult['error']))
				{
					$this->error($uploadResult['error']);
				}
				return self::FAILURE;
			}
		}
		catch (\Exception $e)
		{
			$this->error('Error during upload: ' . $e->getMessage());
			return self::FAILURE;
		}
	}
}
