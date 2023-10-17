<?php
namespace marianojwl\MediaProcessor {
    class OverlaySideTemplate extends Template {
        protected $width;
        protected $height;
        protected $overlay_resource_id;
        protected $newX1;
        protected $newX2;
        protected $newY1;
        protected $newY2;
        protected $side;
        

        
        public function __construct(MediaProcessor $mp, int $id, string $description, string $sufix, $type, $settings) {
            parent::__construct($mp, $id, $description, $sufix, $type, $settings);
            $setts = json_decode( $settings , true);
            $this->width = $setts["width"];
            $this->height = $setts["height"];
            $this->newX1 = $setts["newX1"];
            $this->newX2 = $setts["newX2"];
            $this->newY1 = $setts["newY1"];
            $this->newY2 = $setts["newY2"];
            $this->side = $setts["side"];
            $this->setMimeType($setts["mime_type"]);
            $this->overlay_resource_id = $setts["overlay_resource_id"];
        }
        public function prepare(Request $request, bool $preview = false) {
            $settings = $this->settings;
            // ORIGINAL IMAGE ################################################
            //get resourse
            $r = $request->getResource();

            // Load the original image
            $originalImage = $this->imageCreateFromResource($r);
            $oiW = imagesx($originalImage); // originalImage Width
            $oiH = imagesy($originalImage); // originalImage Height

            // aspect ratio quotient
            $arq = $oiH / $this->height;

            $oiX = 0;
            $oiY = 0;
            
            if($this->side=="left") {
                $oiX = 0;
                $oiY = 0;
            } else if($this->side=="right") {
                $oiX = $oiW-$this->width;
                $oiY = 0;
            } else if($this->side=="center") {
                $oiX = ($oiW-$this->width)/2;
                $oiY = 0;
            }

            
            // Create a new GD image resource for the final image
            $finalImage = imagecreatetruecolor($this->width, $this->height);

            // set values and copy first image to final image
            $dst_image = $finalImage;
            $src_image = $originalImage;
            $dst_x = 0;
            $dst_y = 0;
            $src_x = $oiX;
            $src_y = $oiY;
            $dst_width = $this->width;
            $dst_height = $this->height;
            $src_width = ($this->width * $arq);
            $src_height = $oiH;
                        
            imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x,$src_y,$dst_width,$dst_height,$src_width, $src_height);
            // destroy original image
            imagedestroy($originalImage);

            // OVERLAY ###############################################################

            // get template repository
            $trsr = $this->mp->getTemplateResourcesRepository();

            // get overlay resource
            $overlay_resource = $trsr->getById($this->overlay_resource_id);

            // Load the overlay image with transparency support
            $overlayImage = $this->imageCreateFromResource($overlay_resource);
            $olW = imagesx($overlayImage); // overlay Width
            $olH = imagesy($overlayImage); // overlay Height

            // Overlay the transparent image on top of the resized original image
            imagecopyresampled($finalImage, $overlayImage, 0, 0, 0, 0, $this->width, $this->height, $olW, $olH);
            // destroy overlay image
            imagedestroy($overlayImage);

            if($preview)
                $outputPath = null;
            else
                $outputPath = $this->getOutputPath($r, explode("/", $this->getMimeType())[1]);

            $this->imageSave($finalImage, $outputPath, $this->getMimeType());

            // Clean up resources
            
            //
            imagedestroy($finalImage);


            // Update request status and processed path
            $request->setStatus("processed");
            $request->setProcessedPath($outputPath);
            return $request;
            //$this->mp->getRequestRepository()->save($request);
        }
        
        public function processOld(Request $request, $settings = "{}") {
            $r = $request->getResource();
            $trsr = $this->mp->getTemplateResourcesRepository();
            $overlay_resource = $trsr->getById($this->overlay_resource_id);
            //$rsr->closeConnection();
               
            // Load the original image
            $originalImage = $this->imageCreateFromResource($r);
        
            // Load the overlay image with transparency support
            $overlayImage = $this->imageCreateFromResource($overlay_resource);
        
            // Get the dimensions of the original image
            $originalWidth = imagesx($originalImage);
            $originalHeight = imagesy($originalImage);
        
            // Get the dimensions of the overlay image
            $overlayWidth = imagesx($overlayImage);
            $overlayHeight = imagesy($overlayImage);
        
            // Create a new GD image resource for the final image
            $finalImage = imagecreatetruecolor( $this->width, $this->height );
        
            // Copy the original image to the final image
            //imagecopy($finalImage, $originalImage, 0, 0, 0, 0, $originalWidth, $originalHeight);
            imagecopyresized($finalImage, $originalImage, $this->newX1, $this->newY1, 0, 0, ($this->newX2-$this->newX1), ($this->newY2-$this->newY1), $originalWidth, $originalHeight);
            
            // Calculate the position to center the overlay image on the original image
            //$x = ($originalWidth - $overlayWidth) / 2;
            //$y = ($originalHeight - $overlayHeight) / 2;
        
            // Overlay the transparent image on top of the original image
            //imagecopy($finalImage, $overlayImage, $x, $y, 0, 0, $overlayWidth, $overlayHeight);
            imagecopyresized($finalImage, $overlayImage, 0, 0, 0, 0, $this->width, $this->height, $overlayWidth, $overlayHeight);

            // Define the path where you want to save the resized image
            $outputPath = $this->getOutputPath($r, explode("/",$this->getMimeType())[1]);

            $this->imageSave($finalImage, $outputPath, $this->getMimeType());
        
            // Clean up resources
            imagedestroy($originalImage);
            imagedestroy($overlayImage);
            imagedestroy($finalImage);
        
            //$resource = new Image(null, 1, $outputPath, $this->getMimeType(), false, $this->id);
            $request->setStatus("processed");
            $request->setProcessedPath($outputPath);
            $this->mp->getRequestRepository()->save($request);
            //$resource = $this->storeResource($resource);
        
            //return $resource;
        }

        
        


    }
}