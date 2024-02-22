<?php

namespace TatTran\Repository;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as ImageFacade;

trait UploadTrait
{
    /**
     * Upload an image.
     *
     * @param array $data
     * @return array
     */
    public function upload(array $data): array
    {
        $file = $data['file'];
        $width = $data['width'] ?? 0;
        $height = $data['height'] ?? 0;

        $imageName = $this->generateNewFileName($file);

        try {
            if ($file->getClientOriginalExtension() == 'svg') {
                $file->move(storage_path($this->model->uploadPath), $imageName);
            } else {
                $image = ImageFacade::make($file->getRealPath());

                if ($width && $height) {
                    $image->fit($width, $height);
                }

                $image->save($this->getUploadImagePath($imageName));
            }

            return $this->uploadSuccess($imageName);
        } catch (\Exception | \Throwable $e) {
            return $this->uploadFail($e);
        }
    }

    /**
     * Generate a new file name.
     *
     * @param mixed $file
     * @return string
     */
    public function generateNewFileName($file): string
    {
        $strSecret = '!@#$%^&*()_+QBGFTNKU' . time() . rand(111111, 999999);
        $filenameMd5 = md5($file . $strSecret);
        return date('Y_m_d') . '_' . $filenameMd5 . '.' . $file->getClientOriginalExtension();
    }

    /**
     * Get the image path.
     *
     * @param string $img
     * @return string
     */
    public function getImagePath($img): string
    {
        return asset($this->model->imgPath . '/' . $img);
    }

    /**
     * Get the upload image path.
     *
     * @param string $img
     * @return string
     */
    public function getUploadImagePath($img): string
    {
        $uploadPath = storage_path($this->model->uploadPath);

        if (!File::isDirectory($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true, true);
        }

        return $uploadPath . '/' . $img;
    }

    /**
     * Upload success response.
     *
     * @param string $name
     * @return array
     */
    protected function uploadSuccess($name): array
    {
        return [
            'code' => 1,
            'message' => 'success',
            'data' => [
                'name' => $name,
                'path' => $this->getImagePath($name)
            ]
        ];
    }

    /**
     * Upload fail response.
     *
     * @param \Exception|\Throwable $e
     * @return array
     */
    protected function uploadFail($e): array
    {
        return [
            'code' => 0,
            'message' => 'fail',
            'data' => $e->getMessage()
        ];
    }

    /**
     * Remove an image.
     *
     * @param string $image
     * @return void
     */
    public function removeImage($image): void
    {
        @unlink($this->getUploadImagePath($image));
    }
}
