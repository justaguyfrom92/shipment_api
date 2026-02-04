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
		echo('Starting daily upload to GitHub...');

		try
		{
			echo('Exporting today\'s shipment data...');
			$exportResult = $exportService->exportTodaysShipments();

			if ($exportResult['success'])
			{
				echo("✓ Exported to: {$exportResult['filename']}");
				echo("✓ Total shipments exported: {$exportResult['count']}");
			}
			else
			{
				echo("⚠ {$exportResult['message']}");
			}

			$commitMessage = $this->option('message') . ' - ' . now()->format('Y-m-d H:i:s');
			echo('Uploading to GitHub...');

			$uploadResult = $gitHubService->uploadToGitHub($commitMessage);

			if ($uploadResult['success'])
			{
				echo('✓ ' . $uploadResult['message']);
				return self::SUCCESS;
			}
			else
			{
				echo('✗ ' . $uploadResult['message']);
				if (isset($uploadResult['error']))
				{
					echo($uploadResult['error']);
				}
				return self::FAILURE;
			}
		}
		catch (\Exception $e)
		{
dd($e);
			echo('Error during upload: ' . $e->getMessage());
			return self::FAILURE;
		}
	}
}
