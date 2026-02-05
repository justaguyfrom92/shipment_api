<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;

class GitHubService
{
	public function checkGitInitialized(): bool
	{
		return is_dir(base_path('.git'));
	}

	public function ensureStorageTracked(): void
	{
		$gitignorePath = base_path('.gitignore');

		if (File::exists($gitignorePath))
		{
			$gitignore = File::get($gitignorePath);

			// Remove all storage-related exclusions
			$gitignore = preg_replace('/^\/storage\/?.*$/m', '', $gitignore);
			$gitignore = preg_replace('/^storage\/?.*$/m', '', $gitignore);

			// Clean up multiple blank lines
			$gitignore = preg_replace("/\n{3,}/", "\n\n", $gitignore);

			File::put($gitignorePath, $gitignore);
		}

		// Create .gitignore in storage to keep structure but track files
		$storageGitignorePath = storage_path('.gitignore');
		File::put($storageGitignorePath, "# Track everything in storage\n!*\n");
	}

	public function hasAppChanges(): bool
	{
		$result = Process::path(base_path())->run('git diff --name-only');
		$changedFiles = trim($result->output());

		if (empty($changedFiles))
		{
			return false;
		}

		$changedFilesArray = explode("\n", $changedFiles);

		foreach ($changedFilesArray as $file)
		{
			// Only check for app file changes, not storage/logs
			if (!str_starts_with($file, 'storage/logs/'))
			{
				return true;
			}
		}

		return false;
	}

	public function addChanges(): array
	{
		// Only add if there are app changes
		if (!$this->hasAppChanges())
		{
			return [
				'success' => true,
				'output' => 'No app changes to add',
				'error' => '',
			];
		}

		// Ensure storage is tracked
		$this->ensureStorageTracked();

		// Force add storage folder specifically
		Process::path(base_path())->run('git add -f storage/');

		// Then add everything else
		$result = Process::path(base_path())->run('git add -A');

		return [
			'success' => $result->successful(),
			'output' => $result->output(),
			'error' => $result->errorOutput(),
		];
	}

	public function hasChanges(): bool
	{
		$result = Process::path(base_path())->run('git status --porcelain');
		return !empty(trim($result->output()));
	}

	public function commit(string $message): array
	{
		$result = Process::path(base_path())->run("git commit -m \"{$message}\"");

		return [
			'success' => $result->successful(),
			'output' => $result->output(),
			'error' => $result->errorOutput(),
		];
	}

	public function push(): array
	{
		$result = Process::path(base_path())->run('git push origin main');

		if (!$result->successful())
		{
			$result = Process::path(base_path())->run('git push origin master');
		}

		return [
			'success' => $result->successful(),
			'output' => $result->output(),
			'error' => $result->errorOutput(),
		];
	}

	public function uploadToGitHub(): array
	{
		if (!$this->checkGitInitialized())
		{
			return [
				'success' => false,
				'message' => 'Git repository not initialized',
			];
		}

		// Check if there are app changes (not just log files)
		if (!$this->hasAppChanges())
		{
			return [
				'success' => true,
				'message' => 'No app changes to commit',
			];
		}

		$addResult = $this->addChanges();
		if (!$addResult['success'])
		{
			return [
				'success' => false,
				'message' => 'Failed to add changes',
				'error' => $addResult['error'],
			];
		}

		if (!$this->hasChanges())
		{
			return [
				'success' => true,
				'message' => 'No changes to commit',
			];
		}

		$commitMessage = 'Daily automated commit - ' . now()->format('Y-m-d H:i:s');
		$commitResult = $this->commit($commitMessage);
		if (!$commitResult['success'])
		{
			return [
				'success' => false,
				'message' => 'Failed to commit',
				'error' => $commitResult['error'],
			];
		}

		$pushResult = $this->push();
		if (!$pushResult['success'])
		{
			return [
				'success' => false,
				'message' => 'Failed to push to GitHub',
				'error' => $pushResult['error'],
			];
		}

		return [
			'success' => true,
			'message' => 'Successfully uploaded to GitHub',
		];
	}
}
