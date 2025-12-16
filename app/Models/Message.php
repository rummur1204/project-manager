<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    
    protected $fillable = ['chat_id', 'user_id', 'message', 'read_at', 'file_path'];
    
    protected $casts = [
        'read_at' => 'datetime',
        'file_path' => 'array', // Cast JSON to array
    ];

    public function chat() {
        return $this->belongsTo(Chat::class); 
    }
    
    public function user() {
        return $this->belongsTo(User::class); 
    }
    
    // Get all files with their info
    public function getFilesAttribute()
    {
        if (!$this->file_path) {
            return [];
        }
        
        $files = is_array($this->file_path) ? $this->file_path : json_decode($this->file_path, true);
        
        // Ensure backward compatibility
        if (isset($files[0]) && !is_array($files[0])) {
            // Old format: just paths
            return array_map(function($path) {
                return [
                    'path' => $path,
                    'original_name' => basename($path)
                ];
            }, $files);
        }
        
        return $files;
    }
    
    // Get file paths only (for compatibility)
    public function getFilePathsAttribute()
    {
        return array_column($this->files, 'path');
    }
    
    // Get first file (for compatibility)
    public function getFirstFileAttribute()
    {
        $files = $this->files;
        return $files[0] ?? null;
    }

    // Check if message has files
    public function getHasFilesAttribute()
    {
        return !empty($this->file_path);
    }
    
    // Get file icon based on extension
    public function getFileIconAttribute()
    {
        if (!$this->has_files) {
            return null;
        }
        
        $firstFile = $this->first_file;
        $path = is_array($firstFile) ? $firstFile['path'] : $firstFile;
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $extension = strtolower($extension);
        
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        $docExtensions = ['doc', 'docx'];
        $pdfExtensions = ['pdf'];
        $excelExtensions = ['xls', 'xlsx', 'csv'];
        $pptExtensions = ['ppt', 'pptx'];
        $zipExtensions = ['zip', 'rar', '7z', 'tar', 'gz'];
        
        if (in_array($extension, $imageExtensions)) {
            return '🖼️';
        } elseif (in_array($extension, $docExtensions)) {
            return '📄';
        } elseif (in_array($extension, $pdfExtensions)) {
            return '📕';
        } elseif (in_array($extension, $excelExtensions)) {
            return '📊';
        } elseif (in_array($extension, $pptExtensions)) {
            return '📽️';
        } elseif (in_array($extension, $zipExtensions)) {
            return '📦';
        } else {
            return '📎';
        }
    }
    
    // Get file name from path
    public function getFileNameAttribute()
    {
        if (!$this->has_files) {
            return null;
        }
        
        $firstFile = $this->first_file;
        if (is_array($firstFile)) {
            return $firstFile['original_name'] ?? basename($firstFile['path']);
        }
        
        return basename($firstFile);
    }
    
    // Check if file is an image
    public function getIsImageAttribute()
    {
        if (!$this->has_files) {
            return false;
        }
        
        $firstFile = $this->first_file;
        $path = is_array($firstFile) ? $firstFile['path'] : $firstFile;
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        
        return in_array(strtolower($extension), $imageExtensions);
    }
}