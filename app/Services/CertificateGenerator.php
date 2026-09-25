<?php

namespace App\Services;

use App\Models\EventParticipant;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class CertificateGenerator
{
    protected $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function generate(EventParticipant $participant)
    {
        $event = $participant->event;
        $template = $event->template;

        if (!$template || !$template->template_image) {
            throw new \Exception("Template sertifikat belum diatur untuk event ini.");
        }

        // Validate Eligibility
        if ($event->end_date > now() && $event->status !== 'closed') {
             throw new \Exception("Event belum selesai."); 
        }

        if ($participant->attendances()->count() == 0) {
            throw new \Exception("Peserta belum melakukan absensi.");
        }

        // Load Template
        $templatePath = $template->template_image;
        if (str_starts_with($templatePath, 'assets')) {
             $templatePath = public_path($templatePath);
        } else {
             $templatePath = storage_path('app/public/' . $template->template_image);
        }

        if (!file_exists($templatePath)) {
             throw new \Exception("File template tidak ditemukan.");
        }

        $image = $this->manager->read($templatePath);
        $width = $image->width();
        $height = $image->height();
        $centerX = $width / 2;
        
        // Dynamic Scaling Factor (Benchmark: Width 2000px = Scale 1.0)
        $scale = $width / 2000;
        
        // Colors
        $black = $customColor;
        $darkGrey = $customColor;
        $primaryColor = $customColor;
        
        $printOnlyNameType = (bool) $template->print_only_name_type;

        // Determine Font Path
        $selectedFont = $template->font ?? 'Nunito/Nunito-Bold.ttf';
        $fontPath = public_path('assets/fonts/' . $selectedFont);
        // Fallbacks if not exists
        if (!file_exists($fontPath)) {
            if (file_exists(public_path('assets/fonts/Nunito/Nunito-Bold.ttf'))) {
                $fontPath = public_path('assets/fonts/Nunito/Nunito-Bold.ttf');
            } else {
                $fontPath = public_path('assets/fonts/Nunito-Bold.ttf');
            }
        }

        // 1. Event Logo (Top Center)
        // Position: 10% from top
        if (!$printOnlyNameType && $template->use_event_logo && $event->logo) {
            $logoPath = public_path('assets/images/event-logo/' . $event->logo);
            if (file_exists($logoPath)) {
                $logo = $this->manager->read($logoPath);
                $logoSize = (int)(200 * $scale);
                $logo->scale(height: $logoSize);
                $image->place($logo, 'top-center', 0, (int)($height * 0.05));
            }
        }

        // 2. Title "SERTIFIKAT" (Top 25%)
        if (!$printOnlyNameType) {
            $image->text('SERTIFIKAT', $centerX, (int)($height * 0.25), function ($font) use ($black, $scale, $fontPath) {
                $font->file($fontPath);
                $font->size(75 * $scale);
                $font->color($black);
                $font->align('center');
            });

            // "Diberikan kepada" (Top 30%)
            $image->text('Diberikan kepada', $centerX, (int)($height * 0.30), function ($font) use ($darkGrey, $scale, $fontPath) {
                $font->file($fontPath);
                $font->size(40 * $scale);
                $font->color($darkGrey);
                $font->align('center');
            });
        }

        // 3. Participant Name (Top 57%) - The Highlight
        $participantName = strtoupper($participant->participant->name);
        $image->text($participantName, $centerX, (int)($height * 0.55), function ($font) use ($black, $scale, $fontPath) {
            $font->file($fontPath);
            $font->size(70 * $scale);
            $font->color($black);
            $font->align('center');
        });

        // 4. Body Text (Top 60%)
        // "atas partisipasinya sebagai [TYPE] pada Event [EVENT_NAME]..."
        $type = strtoupper($participant->participantType->name);
        $customText = $participant->participantType->certificate_text ?? "atas partisipasinya sebagai";
        
        if ($printOnlyNameType) {
            $bodyText = "{$customText} {$type}";
        } else {
            $eventName = strtoupper($event->name);
            $dateRange = $event->start_date->translatedFormat('d F Y');
            if($event->start_date->format('Y-m-d') !== $event->end_date->format('Y-m-d')){
                 $dateRange .= " - " . $event->end_date->translatedFormat('d F Y');
            }
            $bodyText = "{$customText} {$type}\npada Event {$eventName}\nTanggal {$dateRange}\ndi " . $event->location;
        }

        $image->text($bodyText, $centerX, (int)($height * 0.58), function ($font) use ($darkGrey, $scale, $fontPath) {
            $font->file($fontPath);
            $font->size(35 * $scale);
            $font->color($darkGrey);
            $font->align('center');
            $font->lineHeight(1.6);
        });

        // 5. Signatures (Bottom 20%, roughly Y=80%)
        if (!$printOnlyNameType) {
            $signatures = $event->signatures;
            if ($signatures->count() > 0) {
                $sigCount = $signatures->count();
            // Calculate spacing based on count
            // width / (count + 1) gives centered segments
            $spacing = $width / ($sigCount + 1);
            $sigY = (int)($height * 0.75);
            $imgSize = 150 * $scale;

            // City and Date Position (Before Signatures)
            // Convention: Place it above the right-most signature, or centered?
            // Usually: "Jakarta, 20 Januari 2024" above the signature block or above the first/last signer.
            // Request said: "sebelum signature". Let's place it above the signatures area, right aligned or center?
            // "untuk diinput admin letakan sebelum signature" -> This refers to the input form order.
            // In the certificate, usually Date is above the signature of the primary authority.
            // If multiple signatures, usually date is just written once or above the main person.
            // Let's place it above the signature line, centered for now or dynamically.
            // Common practice: "City, Date" above the right-most signature.
            $dateText = "";
            if ($template->signature_city || $template->signature_date) {
                $city = $template->signature_city ?? '';
                $date = $template->signature_date ? \Carbon\Carbon::parse($template->signature_date)->translatedFormat('d F Y') : now()->translatedFormat('d F Y');
                $dateText = trim("$city, $date", ", ");
            }

            foreach ($signatures as $index => $sig) {
                $xPos = (int)($spacing * ($index + 1));
                
                // If it's the last signature (right-most), place date above it?
                // Or if only 1 signature, place above it.
                if (!empty($dateText) && ($index == $sigCount - 1)) {
                    $image->text($dateText, $xPos, $sigY - (int)(40 * $scale), function($font) use ($darkGrey, $scale, $fontPath) {
                        $font->file($fontPath);
                        $font->size(28 * $scale);
                        $font->color($darkGrey);
                        $font->align('center');
                    });
                }
                // Title/Jabatan
                $image->text($sig->jabatan, $xPos, $sigY, function($font) use ($darkGrey, $scale, $fontPath) {
                   $font->file($fontPath);
                   $font->size(35 * $scale);
                   $font->color($darkGrey);
                   $font->align('center');
                });

                // Signature Image
                if ($sig->signature_image) {
                     $sigPath = storage_path('app/public/' . $sig->signature_image);
                     if (file_exists($sigPath)) {
                         $sigImg = $this->manager->read($sigPath);
                         $sigImg->scale(height: (int)$imgSize);
                         // Place center of image at xPos, sigY + gap
                         // calculate offset
                         $image->place($sigImg, 'top-left', (int)($xPos - ($sigImg->width() / 2)), $sigY + (int)(30 * $scale));
                     }
                }

                // Name (Below image, approx +180px gap scaled)
                $nameY = $sigY + (int)(180 * $scale);
                $image->text($sig->name, $xPos, $nameY, function($font) use ($black, $scale, $fontPath) {
                   $font->file($fontPath);
                   $font->size(35 * $scale);
                   $font->color($black);
                   $font->align('center');
                });

                // NIP
                if ($sig->nip) {
                    $image->text("NIP. " . $sig->nip, $xPos, $nameY + (int)(35 * $scale), function($font) use ($darkGrey, $scale, $fontPath) {
                       $font->file($fontPath);
                       $font->size(30 * $scale);
                       $font->color($darkGrey);
                       $font->align('center');
                    });
                }
            }
        }
        }

        // 6. QR Code (Bottom Left)
        $token = $participant->qrToken ? $participant->qrToken->token : 'INVALID';
        $verifyUrl = route('event.ticket', ['event' => $event->slug, 'qrToken' => $token]); 
        
        $qrSize = (int)(150 * $scale); 
        $qrCode = (string) QrCode::format('png')->size($qrSize)->margin(1)->generate($verifyUrl);
        
        if (!empty($qrCode)) {
             try {
                 $qrImage = $this->manager->read($qrCode);
                 
                 // Create a white background/border for QR
                 $borderPadding = (int)(10 * $scale);
                 $bgSize = $qrSize + ($borderPadding * 2);
                 
                 // Positioning: Bottom Left, e.g. X=5%, Y=90%
                 $qrX = (int)($width * 0.05);
                 $qrY = (int)($height - $bgSize - ($height * 0.05));

                 // Draw white rectangle background
                 $image->drawRectangle($qrX, $qrY, function ($rectangle) use ($bgSize, $qrX, $qrY) {
                     $rectangle->size($bgSize, $bgSize); // Intervention V3 uses width/height or point? 
                     // V3 API: $image->drawRectangle(int $x, int $y, callable $init)
                     // Actually V2 used rectangle. V3 usually implies creating a shape.
                     // Let's use simpler approach: Paste a white canvas behind it or use border?
                     // Intervention V3 doesn't have drawRectangle in this syntax? 
                     // Check V3 docs: $image->drawRectangle(10, 10, function ($draw) { $draw->background('blue'); $draw->size(100, 200); });
                     // Let's verify Syntax. Or just create a new white image and place it.
                 });
                 // ALTERNATIVE SAFE METHOD: Create new white canvas and place it, then place QR on top.
                 $whiteBg = $this->manager->create($bgSize, $bgSize)->fill('ffffff');
                 $image->place($whiteBg, 'top-left', $qrX, $qrY);
                 
                 // Place QR on top of white BG
                 $image->place($qrImage, 'top-left', $qrX + $borderPadding, $qrY + $borderPadding);

             } catch(\Exception $e) {
                 \Illuminate\Support\Facades\Log::error("QR Embed Failed: " . $e->getMessage());
             }
        }

        return $image;
    }
}
