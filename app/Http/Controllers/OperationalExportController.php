<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Operational\OperationalMetrics;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class OperationalExportController extends Controller
{
    public function download(Request $request, string $format, OperationalMetrics $metrics): StreamedResponse|BinaryFileResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $rows = collect($metrics->forActor($actor))->map(fn ($value, $key): array => [(string) $key, (string) $value])->values()->all();

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($rows): void {
                $stream = fopen('php://output', 'wb');
                fputcsv($stream, ['metric', 'value']);
                foreach ($rows as $row) {
                    fputcsv($stream, $row);
                }
                fclose($stream);
            }, 'safa-karir-operational-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $path = storage_path('framework/cache/report-'.bin2hex(random_bytes(8)).'.xlsx');
        $this->writeXlsx($path, array_merge([['metric', 'value']], $rows));

        return response()->download($path, 'safa-karir-operational-report.xlsx')->deleteFileAfterSend(true);
    }

    /** @param list<array{0:string,1:string}> $rows */
    private function writeXlsx(string $path, array $rows): void
    {
        $zip = new ZipArchive;
        abort_unless($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Operational" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $sheetRows = '';
        foreach ($rows as $index => $row) {
            $number = $index + 1;
            $sheetRows .= '<row r="'.$number.'"><c t="inlineStr"><is><t>'.$this->xml($row[0]).'</t></is></c><c t="inlineStr"><is><t>'.$this->xml($row[1]).'</t></is></c></row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetRows.'</sheetData></worksheet>');
        $zip->close();
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
