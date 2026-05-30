<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Shipment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\ShipmentExportService;

class FixShipmentLogs extends Command
{
	protected $signature = 'fix:logs';
	protected $description = 'Makes sure all shipment logs file names are set to YYYY_MM_DD format';

	public function handle(ShipmentExportService $exportService)
	{
		//gets all the products and then check to see if the name is the correct format
		//if not the correct format then update correctly

		$shipments = Shipment::get();
		foreach($shipments as $shipment)
		{
			$date = Carbon::parse($shipment->shipment_date);
	                $date->setTime(0, 0, 0);
	                $timestamp = $date->format('Y-m-d_His');
			$shipmentsDir = storage_path('logs/shipments');
	                $filename = "{$shipmentsDir}/{$timestamp}.json";
			$file = 'storage/logs/shipments/'."{$timestamp}.json";

			$fileExists = false;

			/***
			if (File::exists($filename))
			{
				$shipment->filename = $file;
				$shipment->save();

				$fileExists = true;
			}
			else
			{***/
				// find and delete files
				$datePrefix = $date->format('Y-m-d_');
				$pattern = "{$shipmentsDir}/{$datePrefix}*.json";

				$matchingFiles = File::glob($pattern);
				if (!empty($matchingFiles))
				{
					$firstFileAbsolutePath = array_shift($matchingFiles);

					$newLocalPath = 'storage/logs/shipments/'."{$timestamp}.json";
					$newAbsoluteTarget = "{$shipmentsDir}/{$timestamp}.json";

					if ($firstFileAbsolutePath !== $newAbsoluteTarget)
					{
						File::move($firstFileAbsolutePath, $newAbsoluteTarget);
					}

					$shipment->filename = $newLocalPath;
					$shipment->save();
					$fileExists = true;

					foreach ($matchingFiles as $filename)
					{
						File::delete($filename);
					}

					$matchingFiles = File::glob($pattern);
				}
			//}


			if(!$fileExists)
			{
				//needs to create new file is one got deleted
				$exportResult = $exportService->exportShipmentForDate($date);
				if ($exportResult['success'])
	                        {
	                                echo("    ✓ Exported log: {$exportResult['filename']}");
	                                $shipment->filename = $exportResult['filename'] ?? 'FILENAME';
					$shipment->save();
	                        }
			}
		}
	}
}


















