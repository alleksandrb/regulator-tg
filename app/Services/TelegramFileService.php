<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Exception;

class TelegramFileService
{
    private const SESSION_DIR = 'telegram/sessions';
    private const JSON_DIR = 'telegram/json';

    public function __construct()
    {
        $this->ensureDirectoriesExist();
    }

    /**
     * Сохранить файлы сессии и JSON на диск
     * 
     * @param string $sessionData - содержимое файла сессии
     * @param string $jsonData - содержимое JSON файла
     * @param string $filename - пользовательское имя файла (без расширения)
     * @return string - уникальное имя файла (без расширения)
     * @throws Exception
     */
    public function saveFiles(string $sessionData, string $jsonData, string $filename): string
    {
        if ($this->fileExists($filename)) {
            throw new Exception("Файл с именем '{$filename}' уже существует");
        }

        try {
            // Сохраняем файл сессии
            $sessionPath = self::SESSION_DIR . '/' . $filename . '.session';
            if (!Storage::disk('local')->put($sessionPath, $sessionData)) {
                throw new Exception('Ошибка сохранения файла сессии');
            }

            // Сохраняем JSON файл
            $jsonPath = self::JSON_DIR . '/' . $filename . '.json';
            if (!Storage::disk('local')->put($jsonPath, $jsonData)) {
                // Если JSON не сохранился, удаляем файл сессии
                Storage::disk('local')->delete($sessionPath);
                throw new Exception('Ошибка сохранения JSON файла');
            }

            return $filename;
        } catch (Exception $e) {
            // Очищаем частично созданные файлы
            $this->deleteFiles($filename);
            throw $e;
        }
    }

    /**
     * Получить содержимое JSON файла
     * 
     * @param string $filename - имя файла без расширения
     * @return array - декодированные JSON данные
     * @throws Exception
     */
    public function getJsonData(string $filename): array
    {
        $jsonPath = self::JSON_DIR . '/' . $filename . '.json';
        
        if (!Storage::disk('local')->exists($jsonPath)) {
            throw new Exception("JSON файл '{$filename}.json' не найден");
        }

        $content = Storage::disk('local')->get($jsonPath);
        $decoded = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Ошибка декодирования JSON файла '{$filename}.json': " . json_last_error_msg());
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Удалить файлы сессии и JSON
     * 
     * @param string $filename - имя файла без расширения
     * @return bool - успешность операции
     */
    public function deleteFiles(string $filename): bool
    {
        $sessionPath = self::SESSION_DIR . '/' . $filename . '.session';
        $jsonPath = self::JSON_DIR . '/' . $filename . '.json';
        
        $sessionDeleted = !Storage::disk('local')->exists($sessionPath) || Storage::disk('local')->delete($sessionPath);
        $jsonDeleted = !Storage::disk('local')->exists($jsonPath) || Storage::disk('local')->delete($jsonPath);
        
        return $sessionDeleted && $jsonDeleted;
    }

    /**
     * Проверить существование файлов
     * 
     * @param string $filename - имя файла без расширения
     * @return bool
     */
    public function fileExists(string $filename): bool
    {
        $sessionPath = self::SESSION_DIR . '/' . $filename . '.session';
        $jsonPath = self::JSON_DIR . '/' . $filename . '.json';
        
        return Storage::disk('local')->exists($sessionPath) || Storage::disk('local')->exists($jsonPath);
    }


    /**
     * Убедиться что необходимые директории существуют
     */
    private function ensureDirectoriesExist(): void
    {
        $directories = [self::SESSION_DIR, self::JSON_DIR];
        
        foreach ($directories as $dir) {
            if (!Storage::disk('local')->exists($dir)) {
                Storage::disk('local')->makeDirectory($dir);
            }
        }
    }

    /**
     * Получить путь к директории сессий
     * 
     * @return string
     */
    public function getSessionsPath(): string
    {
        return Storage::disk('local')->path(self::SESSION_DIR);
    }

    /**
     * Получить путь к директории JSON файлов
     * 
     * @return string
     */
    public function getJsonPath(): string
    {
        return Storage::disk('local')->path(self::JSON_DIR);
    }
}
