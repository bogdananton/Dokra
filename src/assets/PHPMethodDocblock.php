<?php
namespace Dokra\assets;

class PHPMethodDocblock
{
    public $Signature;
    public $Documentation;
    public $Return = array();
    public $Throws = array();
    public $Params = array();

    public function __construct($raw)
    {
        $signatureRaw = array_pop($raw);
        $docblockRaw = $raw;

        $Signature = new PHPSignature($signatureRaw);

        $Documentation = array();
        $docblockRawParams = array();

        foreach ($docblockRaw as $docblockRawEntry) {
            $docblockRawEntry = trim($docblockRawEntry);
            if (!in_array($docblockRawEntry, array('*', '/*', '/**', '*/'))) {
                if (substr($docblockRawEntry, 0, 1) == '*') {
                    $docblockRawEntry = trim(substr($docblockRawEntry, 1));
                }

                if (substr($docblockRawEntry, 0, 1) == '@') {
                    $param = PHPParam::getInstance($docblockRawEntry, $Signature);

                    switch (get_class($param)) {
                        case 'Dokra\assets\PHPParam':
                            $this->Params[] = $param;
                            break;

                        case 'Dokra\assets\PHPThrows':
                            $this->Throws[] = $param;
                            break;

                        case 'Dokra\assets\PHPReturn':
                            $this->Return[] = $param;
                            break;
                        
                        default:
                            // throw new \Exception("Uncaught PHP parameter type " . get_class($param));
                            
                            break;
                    }

                } else {
                    $Documentation[] = $docblockRawEntry;
                }
            }
        }

        $this->Signature = $Signature;
        $this->Documentation = is_array($Documentation) ? implode("\n", $Documentation) : $Documentation;
    }
}
