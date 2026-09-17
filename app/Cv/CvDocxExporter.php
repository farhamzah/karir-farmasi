<?php

namespace App\Cv;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

final class CvDocxExporter
{
    /** @param array<string, mixed> $snapshot */
    public function export(array $snapshot, ?string $photoDataUri = null): string
    {
        $directory = storage_path('app/private/career/cv-export');
        File::ensureDirectoryExists($directory);
        $path = $directory.'/'.bin2hex(random_bytes(12)).'.docx';
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak dapat membuat dokumen DOCX.');
        }

        $photo = $this->photo($photoDataUri);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes($photo['extension'] ?? null));
        $zip->addFromString('_rels/.rels', $this->packageRelationships());
        $zip->addFromString('word/document.xml', $this->document($snapshot, $photo !== null));
        $zip->addFromString('word/styles.xml', $this->styles($snapshot['template']['key'] ?? 'cv-01'));
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelationships($photo['extension'] ?? null));
        $zip->addFromString('docProps/core.xml', $this->coreProperties());
        if ($photo !== null) {
            $zip->addFromString('word/media/profile.'.$photo['extension'], $photo['bytes']);
        }
        $zip->close();

        return $path;
    }

    /** @param array<string, mixed> $cv */
    private function document(array $cv, bool $hasPhoto): string
    {
        $parts = [];
        $parts[] = $this->paragraph((string) ($cv['professional_name'] ?? ''), 'CvName');
        if ($hasPhoto) {
            $parts[] = $this->photoDrawing();
        }
        if (filled($cv['headline'] ?? null)) {
            $parts[] = $this->paragraph((string) $cv['headline'], 'CvHeadline');
        }
        $contacts = array_filter([
            $cv['city'] ?? null,
            $cv['email'] ?? null,
            $cv['whatsapp'] ?? null,
            filled($cv['linkedin_url'] ?? null) ? 'LinkedIn' : null,
            filled($cv['portfolio_url'] ?? null) ? 'Portofolio' : null,
        ]);
        if ($contacts !== []) {
            $parts[] = $this->paragraph(implode('  •  ', $contacts), 'CvContact');
        }
        foreach ($cv['sections'] ?? [] as $section) {
            $parts[] = $this->paragraph((string) ($section['title'] ?? ''), 'CvSection');
            foreach ($section['items'] ?? [] as $item) {
                $values = collect($item)
                    ->reject(fn ($value) => $value === null || $value === '' || is_bool($value))
                    ->map(fn ($value, string $field) => $this->displayValue($field, (string) $value))
                    ->values()->all();
                if ($values !== []) {
                    $parts[] = $this->paragraph(implode(' · ', $values), 'CvItem');
                }
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><w:body>'
            .implode('', $parts)
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr></w:body></w:document>';
    }

    private function paragraph(string $text, string $style): string
    {
        return '<w:p><w:pPr><w:pStyle w:val="'.$style.'"/></w:pPr><w:r><w:t xml:space="preserve">'.$this->xml($text).'</w:t></w:r></w:p>';
    }

    private function displayValue(string $field, string $value): string
    {
        return match ($field) {
            'credential_url' => 'Sertifikat',
            'project_url' => 'Proyek',
            'url' => str_contains(strtolower($value), 'scholar.google') ? 'Google Scholar' : 'Publikasi',
            default => $value,
        };
    }

    private function styles(string $templateKey): string
    {
        $accent = match ($templateKey) {
            'cv-03' => 'A95835', 'cv-04' => '17392E', 'cv-05' => '465C69', default => '1F6B4F'
        };

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:rPr><w:sz w:val="21"/><w:color w:val="26352E"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="CvName"><w:name w:val="CV Name"/><w:pPr><w:spacing w:after="120"/></w:pPr><w:rPr><w:b/><w:sz w:val="38"/><w:color w:val="'.$accent.'"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="CvHeadline"><w:name w:val="CV Headline"/><w:rPr><w:i/><w:sz w:val="24"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="CvContact"><w:name w:val="CV Contact"/><w:pPr><w:spacing w:after="240"/></w:pPr><w:rPr><w:sz w:val="18"/><w:color w:val="596760"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="CvSection"><w:name w:val="CV Section"/><w:pPr><w:spacing w:before="240" w:after="100"/><w:keepNext/></w:pPr><w:rPr><w:b/><w:sz w:val="24"/><w:color w:val="'.$accent.'"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="CvItem"><w:name w:val="CV Item"/><w:pPr><w:spacing w:after="100"/><w:keepLines/></w:pPr></w:style>'
            .'</w:styles>';
    }

    private function contentTypes(?string $photoExtension): string
    {
        $photoType = $photoExtension ? '<Default Extension="'.$photoExtension.'" ContentType="image/'.($photoExtension === 'jpg' ? 'jpeg' : 'png').'"/>' : '';

        return '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'.$photoType.'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/></Types>';
    }

    private function packageRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/></Relationships>';
    }

    private function documentRelationships(?string $photoExtension): string
    {
        $photoRelationship = $photoExtension ? '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/profile.'.$photoExtension.'"/>' : '';

        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'.$photoRelationship.'</Relationships>';
    }

    private function photoDrawing(): string
    {
        return '<w:p><w:r><w:drawing><wp:inline><wp:extent cx="1143000" cy="1143000"/><wp:docPr id="1" name="Foto profil"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="Foto profil"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="rId2"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="1143000" cy="1143000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
    }

    /** @return array{extension: 'jpg'|'png', bytes: string}|null */
    private function photo(?string $dataUri): ?array
    {
        if (! $dataUri || ! preg_match('/^data:image\/(jpeg|png);base64,(.+)$/s', $dataUri, $matches)) {
            return null;
        }
        $bytes = base64_decode($matches[2], true);

        return $bytes === false ? null : ['extension' => $matches[1] === 'jpeg' ? 'jpg' : 'png', 'bytes' => $bytes];
    }

    private function coreProperties(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>Curriculum Vitae</dc:title><dc:creator>SAFA KARIR</dc:creator></cp:coreProperties>';
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
