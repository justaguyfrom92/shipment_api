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

	public function hasRelevantChanges(): bool
	{
		$allFiles = [];

		// Check for modified/staged files
		$result = Process::path(base_path())->run('git diff --name-only');
		$modifiedFiles = trim($result->output());

		if (!empty($modifiedFiles))
		{
			$allFiles = array_merge($allFiles, explode("\n", $modifiedFiles));
		}

		// Check for staged changes
		$result = Process::path(base_path())->run('git diff --cached --name-only');
		$stagedFiles = trim($result->output());

		if (!empty($stagedFiles))
		{
			$allFiles = array_merge($allFiles, explode("\n", $stagedFiles));
		}

		// Check for untracked files (this is the key part for new files)
		$result = Process::path(base_path())->run('git ls-files --others --exclude-standard');
		$untrackedFiles = trim($result->output());

		if (!empty($untrackedFiles))
		{
			$allFiles = array_merge($allFiles, explode("\n", $untrackedFiles));
		}

		// Filter out empty entries
		$allFiles = array_filter($allFiles, fn($file) => !empty($file));

		if (empty($allFiles))
		{
			return false;
		}

		// Check if any file is relevant (not schedule-commands.log)
		foreach ($allFiles as $file)
		{
			// Exclude only the schedule commands log file
			if (str_ends_with($file, 'schedule-commands.log'))
			{
				continue;
			}

			// Any other file change is relevant (including storage/logs files)
			return true;
		}

		return false;
	}

	public function addChanges(): array
	{
		// Only add if there are relevant changes
		if (!$this->hasRelevantChanges())
		{
			return [
				'success' => true,
				'output' => 'No relevant changes to add',
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

		// Check if there are relevant changes (excluding schedule commands log)
		if (!$this->hasRelevantChanges())
		{
			return [
				'success' => true,
				'message' => 'No relevant changes to commit',
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
