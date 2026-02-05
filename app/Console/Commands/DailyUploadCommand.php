<?php

namespace App\Console\Commands;

use App\Services\GitHubService;
use Illuminate\Console\Command;

class DailyUploadCommand extends Command
{
	protected $signature = 'daily:upload';
	protected $description = 'Upload changes to GitHub if there are app file changes';

	public function handle(GitHubService $gitHubService): int
	{
		$this->info('Starting daily upload to GitHub...');

		try
		{
			$uploadResult = $gitHubService->uploadToGitHub();

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
