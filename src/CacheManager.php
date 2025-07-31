<?php
namespace Game;

/**
 * Cache Management System
 * 
 * Provides file-based caching with TTL support, cache invalidation,
 * and automatic cleanup. Improves application performance by caching
 * frequently accessed data and expensive operations.
 * 
 * @package Game
 * @author Choose Your Path RPG Team
 * @version 1.0.0
 */
class CacheManager 
{
    /** @var string Cache directory path */
    private const CACHE_DIR = __DIR__ . '/../cache/';
    
    /** @var int Default cache TTL in seconds (1 hour) */
    private const DEFAULT_TTL = 3600;
    
    /** @var array In-memory cache for current request */
    private static $memoryCache = [];
    
    /** @var bool Whether caching is enabled */
    private static $enabled = true;
    
    /**
     * Initialize cache manager
     * 
     * @param bool $enabled Whether to enable caching
     */
    public static function init(bool $enabled = true): void 
    {
        self::$enabled = $enabled;
        
        if (!self::$enabled) {
            return;
        }
        
        // Ensure cache directory exists
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
        
        // Clean up expired cache files periodically
        if (rand(1, 100) === 1) { // 1% chance
            self::cleanup();
        }
    }
    
    /**
     * Store data in cache
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int|null $ttl Time to live in seconds
     * @return bool True on success
     */
    public static function set(string $key, $data, ?int $ttl = null): bool 
    {
        if (!self::$enabled) {
            return false;
        }
        
        $ttl = $ttl ?? self::DEFAULT_TTL;
        $cacheKey = self::sanitizeKey($key);
        
        // Store in memory cache
        self::$memoryCache[$cacheKey] = [
            'data' => $data,
            'expires' => time() + $ttl
        ];
        
        // Store in file cache
        $cacheData = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time(),
            'key' => $key
        ];
        
        $filePath = self::CACHE_DIR . $cacheKey . '.cache';
        $serialized = serialize($cacheData);
        
        return file_put_contents($filePath, $serialized, LOCK_EX) !== false;
    }
    
    /**
     * Retrieve data from cache
     * 
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed Cached data or default value
     */
    public static function get(string $key, $default = null) 
    {
        if (!self::$enabled) {
            return $default;
        }
        
        $cacheKey = self::sanitizeKey($key);
        
        // Check memory cache first
        if (isset(self::$memoryCache[$cacheKey])) {
            $cached = self::$memoryCache[$cacheKey];
            if ($cached['expires'] > time()) {
                return $cached['data'];
            } else {
                unset(self::$memoryCache[$cacheKey]);
            }
        }
        
        // Check file cache
        $filePath = self::CACHE_DIR . $cacheKey . '.cache';
        
        if (!file_exists($filePath)) {
            return $default;
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            return $default;
        }
        
        $cacheData = unserialize($content);
        if ($cacheData === false) {
            // Corrupted cache file, remove it
            unlink($filePath);
            return $default;
        }
        
        // Check if expired
        if ($cacheData['expires'] <= time()) {
            unlink($filePath);
            return $default;
        }
        
        // Store in memory cache for subsequent requests
        self::$memoryCache[$cacheKey] = [
            'data' => $cacheData['data'],
            'expires' => $cacheData['expires']
        ];
        
        return $cacheData['data'];
    }
    
    /**
     * Check if cache key exists and is valid
     * 
     * @param string $key Cache key
     * @return bool True if exists and valid
     */
    public static function has(string $key): bool 
    {
        if (!self::$enabled) {
            return false;
        }
        
        $cacheKey = self::sanitizeKey($key);
        
        // Check memory cache
        if (isset(self::$memoryCache[$cacheKey])) {
            return self::$memoryCache[$cacheKey]['expires'] > time();
        }
        
        // Check file cache
        $filePath = self::CACHE_DIR . $cacheKey . '.cache';
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }
        
        $cacheData = unserialize($content);
        if ($cacheData === false) {
            unlink($filePath);
            return false;
        }
        
        return $cacheData['expires'] > time();
    }
    
    /**
     * Delete cache entry
     * 
     * @param string $key Cache key
     * @return bool True on success
     */
    public static function delete(string $key): bool 
    {
        $cacheKey = self::sanitizeKey($key);
        
        // Remove from memory cache
        unset(self::$memoryCache[$cacheKey]);
        
        // Remove from file cache
        $filePath = self::CACHE_DIR . $cacheKey . '.cache';
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * Clear all cache entries
     * 
     * @return bool True on success
     */
    public static function clear(): bool 
    {
        // Clear memory cache
        self::$memoryCache = [];
        
        // Clear file cache
        $files = glob(self::CACHE_DIR . '*.cache');
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get or set cache with callback
     * 
     * @param string $key Cache key
     * @param callable $callback Function to generate data if not cached
     * @param int|null $ttl Time to live in seconds
     * @return mixed Cached or generated data
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null) 
    {
        $data = self::get($key);
        
        if ($data !== null) {
            return $data;
        }
        
        $data = $callback();
        self::set($key, $data, $ttl);
        
        return $data;
    }
    
    /**
     * Clean up expired cache files
     * 
     * @return int Number of files cleaned up
     */
    public static function cleanup(): int 
    {
        $cleaned = 0;
        $files = glob(self::CACHE_DIR . '*.cache');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }
            
            $cacheData = unserialize($content);
            if ($cacheData === false || $cacheData['expires'] <= time()) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public static function getStats(): array 
    {
        $files = glob(self::CACHE_DIR . '*.cache');
        $totalSize = 0;
        $validFiles = 0;
        $expiredFiles = 0;
        
        foreach ($files as $file) {
            $size = filesize($file);
            $totalSize += $size;
            
            $content = file_get_contents($file);
            if ($content !== false) {
                $cacheData = unserialize($content);
                if ($cacheData !== false) {
                    if ($cacheData['expires'] > time()) {
                        $validFiles++;
                    } else {
                        $expiredFiles++;
                    }
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $validFiles,
            'expired_files' => $expiredFiles,
            'total_size' => $totalSize,
            'memory_cache_entries' => count(self::$memoryCache),
            'cache_directory' => self::CACHE_DIR,
            'enabled' => self::$enabled
        ];
    }
    
    /**
     * Sanitize cache key for file system
     * 
     * @param string $key Original key
     * @return string Sanitized key
     */
    private static function sanitizeKey(string $key): string 
    {
        // Replace invalid characters with underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        
        // Limit length and add hash for uniqueness
        if (strlen($sanitized) > 100) {
            $sanitized = substr($sanitized, 0, 80) . '_' . md5($key);
        }
        
        return $sanitized;
    }
    
    /**
     * Enable or disable caching
     * 
     * @param bool $enabled Whether to enable caching
     */
    public static function setEnabled(bool $enabled): void 
    {
        self::$enabled = $enabled;
    }
    
    /**
     * Check if caching is enabled
     * 
     * @return bool True if enabled
     */
    public static function isEnabled(): bool 
    {
        return self::$enabled;
    }
    
    /**
     * Cache scene data for faster loading
     * 
     * @param string $sceneName Scene name
     * @return array|null Scene data or null if not found
     */
    public static function getScene(string $sceneName): ?array 
    {
        return self::remember("scene_{$sceneName}", function() use ($sceneName) {
            $scenePath = __DIR__ . "/../scenes/{$sceneName}.php";
            
            if (!file_exists($scenePath)) {
                return null;
            }
            
            return require $scenePath;
        }, 1800); // Cache for 30 minutes
    }
    
    /**
     * Cache leaderboard data
     * 
     * @return array Leaderboard data
     */
    public static function getLeaderboard(): array 
    {
        return self::remember('leaderboard_data', function() {
            $db = Database::getInstance();
            $stmt = $db->execute('
                SELECT username, ending_reached, playtime_minutes, final_hp, choices_count, completed_at
                FROM leaderboard 
                ORDER BY completed_at DESC 
                LIMIT 50
            ');
            return $stmt->fetchAll();
        }, 300); // Cache for 5 minutes
    }
}
