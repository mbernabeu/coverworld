<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

class Sftp extends AbstractType
{
    const SFTP_SOURCE = 'Firebear\ImportExport\Model\Filesystem\Io\Sftp';

    /**
     * @var string
     */
    protected $_code = 'sftp';

    /**
     * Download remote source file to temporary directory
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        if ($client = $this->_getSourceClient()) {
            $sourceFilePath = $this->getData($this->_code . '_file_path');
            $fileName = basename($sourceFilePath);
            $filePath = $this->directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
            $filesystem = new \Magento\Framework\Filesystem\Io\File();
            $filesystem->setAllowCreateFolders(true);
            $filesystem->checkAndCreateFolder($this->directory->getAbsolutePath($this->getImportPath()));
            $result = $client->read($sourceFilePath, $filePath);

            if ($result) {
                return $this->directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__("File not found"));
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__("Can't initialize %s client", $this->code));
        }
    }

    /**
     * Download remote images to temporary media directory
     *
     * @param $importImage
     * @param $imageSting
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importImage($importImage, $imageSting)
    {
        if ($client = $this->_getSourceClient()) {
            $sourceFilePath = $this->getData($this->code . '_file_path');
            $sourceDirName = dirname($sourceFilePath);
            $filePath = $this->directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
            $dirname = dirname($filePath);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
            }
            if ($filePath) {
                $result = $client->read($sourceDirName . '/' . $importImage, $filePath);
            }
        }
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param int $timestamp
     * @return bool|int
     */
    public function checkModified($timestamp)
    {
        if ($client = $this->_getSourceClient()) {
            $sourceFilePath = $this->getData($this->_code . '_file_path');

            if (!$this->metadata) {
                $this->metadata['modified'] = $client->mdtm($sourceFilePath);
            }

            $modified = $this->metadata['modified'];

            return ($timestamp != $this->metadata['modified']) ? $modified : false;
        }

        return false;
    }

    /**
     * Prepare and return SFTP client
     *
     * @return \Firebear\ImportExport\Model\Filesystem\Io\Ftp
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getSourceClient()
    {
        if (!$this->getClient()) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')
                ->debug(json_encode($this->getData()));
            if ($this->getData('host')
                && $this->getData('port')
                && $this->getData('username')
                && $this->getData('password')) {
                $settings = $this->getData();
            } else {
                $settings = $this->scopeConfig->getValue(
                    'firebear_importexport/sftp',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
            }
            $settings['passive'] = true;
            try {
                $connection = $this->factory->create(self::SFTP_SOURCE);
                $connection->open(
                    $settings
                );
                $this->client = $connection;
            } catch (\Exception $e) {
                throw new  \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        }

        return $this->getClient();
    }

    /**
     * @param $model
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function run($model)
    {
        $result = true;
        $errors = [];
        $path = '';
        try {
        $this->setExportModel($model);
        $name = 'export_' . $this->timezone->date()->format('Y_m_d_H_i_s');
        $path = AbstractType::EXPORT_DIR . "/" . $name;
        if ($this->writeFile($path)) {
            if ($client = $this->_getSourceClient()) {
                $sourceFilePath = $this->getData('file_path');
                $filePath = $this->directory->getAbsolutePath($path);
                $result = $client->write($sourceFilePath, $filePath);
                if (!$result) {
                    $result = false;
                    $errors[] = __('File not found');
                }
            } else {
                $result = false;
                $errors[] = __("Can't initialize %s client", $this->code);
            }
        }
        } catch (\Exception $e) {
            $result = false;
            $errors[] = __('Folder for import / export don\'t have enough permissions! Please set 775');
        }

        return [$result, $path, $errors];
    }
}
