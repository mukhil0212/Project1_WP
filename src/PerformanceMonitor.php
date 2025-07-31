<?php
namespace Game;

/**
 * Performance Monitoring and Profiling System
 * 
 * Tracks application performance metrics, database queries, memory usage,
 * and execution times to identify bottlenecks and optimization opportunities.
 * 
 * @package Game
 * @author Choose Your Path RPG Team
 * @version 1.0.0
 */
class PerformanceMonitor 
{
    /** @var array Performance metrics storage */
    private static $metrics = [];
    
    /** @var array Active timers */
    private static $timers = [];
    
    /** @var array Database query log */
    private static $queries = [];
    
    /** @var float Script start time */
    private static $startTime;
    
    /** @var int Initial memory usage */
    private static $startMemory;
    
    /** @var bool Whether monitoring is enabled */
    private static $enabled = true;
    
    /** @var string Log file path */
    private const LOG_FILE = __DIR__ . '/../logs/performance.log';
    
    /**
     * Initialize performance monitoring
     * 
     * @param bool $enabled Whether to enable monitoring
     */
    public static function init(bool $enabled = true): void 
    {
        self::$enabled = $enabled;
        
        if (!self::$enabled) {
            return;
        }
        
        self::$startTime = microtime(true);
        self::$startMemory = memory_get_usage(true);
        
        // Ensure log directory exists
        $logDir = dirname(self::LOG_FILE);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Register shutdown function to log final metrics
        register_shutdown_function([self::class, 'logFinalMetrics']);
    }
    
    /**
     * Start a performance timer
     * 
     * @param string $name Timer name
     * @param array $context Additional context
     */
    public static function startTimer(string $name, array $context = []): void 
    {
        if (!self::$enabled) {
            return;
        }
        
        self::$timers[$name] = [
            'start' => microtime(true),
            'context' => $context
        ];
    }
    
    /**
     * Stop a performance timer and record the metric
     * 
     * @param string $name Timer name
     * @return float|null Elapsed time in seconds, null if timer not found
     */
    public static function stopTimer(string $name): ?float 
    {
        if (!self::$enabled || !isset(self::$timers[$name])) {
            return null;
        }
        
        $elapsed = microtime(true) - self::$timers[$name]['start'];
        
        self::$metrics[] = [
            'type' => 'timer',
            'name' => $name,
            'duration' => $elapsed,
            'context' => self::$timers[$name]['context'],
            'timestamp' => time(),
            'memory' => memory_get_usage(true)
        ];
        
        unset(self::$timers[$name]);
        
        return $elapsed;
    }
    
    /**
     * Record a database query for monitoring
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param float $duration Execution time in seconds
     */
    public static function recordQuery(string $query, array $params = [], float $duration = 0): void 
    {
        if (!self::$enabled) {
            return;
        }
        
        self::$queries[] = [
            'query' => $query,
            'params' => $params,
            'duration' => $duration,
            'timestamp' => time(),
            'memory' => memory_get_usage(true)
        ];
        
        self::$metrics[] = [
            'type' => 'database',
            'query' => self::sanitizeQuery($query),
            'duration' => $duration,
            'timestamp' => time(),
            'memory' => memory_get_usage(true)
        ];
    }
    
    /**
     * Record a custom metric
     * 
     * @param string $name Metric name
     * @param mixed $value Metric value
     * @param string $type Metric type
     * @param array $context Additional context
     */
    public static function recordMetric(string $name, $value, string $type = 'custom', array $context = []): void 
    {
        if (!self::$enabled) {
            return;
        }
        
        self::$metrics[] = [
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'context' => $context,
            'timestamp' => time(),
            'memory' => memory_get_usage(true)
        ];
    }
    
    /**
     * Get current performance statistics
     * 
     * @return array Performance statistics
     */
    public static function getStats(): array 
    {
        if (!self::$enabled) {
            return [];
        }
        
        $currentTime = microtime(true);
        $currentMemory = memory_get_usage(true);
        
        $stats = [
            'execution_time' => $currentTime - self::$startTime,
            'memory_usage' => $currentMemory,
            'memory_delta' => $currentMemory - self::$startMemory,
            'peak_memory' => memory_get_peak_usage(true),
            'query_count' => count(self::$queries),
            'metric_count' => count(self::$metrics),
            'active_timers' => count(self::$timers)
        ];
        
        // Calculate query statistics
        if (!empty(self::$queries)) {
            $queryTimes = array_column(self::$queries, 'duration');
            $stats['query_stats'] = [
                'total_time' => array_sum($queryTimes),
                'avg_time' => array_sum($queryTimes) / count($queryTimes),
                'max_time' => max($queryTimes),
                'min_time' => min($queryTimes)
            ];
        }
        
        // Calculate timer statistics
        $timerMetrics = array_filter(self::$metrics, function($m) {
            return $m['type'] === 'timer';
        });
        
        if (!empty($timerMetrics)) {
            $timerTimes = array_column($timerMetrics, 'duration');
            $stats['timer_stats'] = [
                'total_time' => array_sum($timerTimes),
                'avg_time' => array_sum($timerTimes) / count($timerTimes),
                'max_time' => max($timerTimes),
                'min_time' => min($timerTimes)
            ];
        }
        
        return $stats;
    }
    
    /**
     * Get slow queries (above threshold)
     * 
     * @param float $threshold Threshold in seconds
     * @return array Slow queries
     */
    public static function getSlowQueries(float $threshold = 0.1): array 
    {
        if (!self::$enabled) {
            return [];
        }
        
        return array_filter(self::$queries, function($query) use ($threshold) {
            return $query['duration'] > $threshold;
        });
    }
    
    /**
     * Get performance bottlenecks
     * 
     * @return array Identified bottlenecks
     */
    public static function getBottlenecks(): array 
    {
        if (!self::$enabled) {
            return [];
        }
        
        $bottlenecks = [];
        $stats = self::getStats();
        
        // Check execution time
        if ($stats['execution_time'] > 2.0) {
            $bottlenecks[] = [
                'type' => 'execution_time',
                'severity' => 'high',
                'message' => 'Page execution time is high: ' . round($stats['execution_time'], 3) . 's',
                'recommendation' => 'Consider optimizing database queries and reducing computational complexity'
            ];
        }
        
        // Check memory usage
        if ($stats['memory_usage'] > 50 * 1024 * 1024) { // 50MB
            $bottlenecks[] = [
                'type' => 'memory_usage',
                'severity' => 'medium',
                'message' => 'High memory usage: ' . self::formatBytes($stats['memory_usage']),
                'recommendation' => 'Review memory-intensive operations and consider optimization'
            ];
        }
        
        // Check query count
        if ($stats['query_count'] > 20) {
            $bottlenecks[] = [
                'type' => 'query_count',
                'severity' => 'medium',
                'message' => 'High number of database queries: ' . $stats['query_count'],
                'recommendation' => 'Consider query optimization and caching strategies'
            ];
        }
        
        // Check slow queries
        $slowQueries = self::getSlowQueries(0.1);
        if (!empty($slowQueries)) {
            $bottlenecks[] = [
                'type' => 'slow_queries',
                'severity' => 'high',
                'message' => 'Found ' . count($slowQueries) . ' slow queries',
                'recommendation' => 'Optimize slow queries with indexes and query restructuring'
            ];
        }
        
        return $bottlenecks;
    }
    
    /**
     * Log final metrics at script end
     */
    public static function logFinalMetrics(): void 
    {
        if (!self::$enabled) {
            return;
        }
        
        $stats = self::getStats();
        $bottlenecks = self::getBottlenecks();
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'stats' => $stats,
            'bottlenecks' => $bottlenecks
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        error_log($logLine, 3, self::LOG_FILE);
    }
    
    /**
     * Generate performance report
     * 
     * @return array Comprehensive performance report
     */
    public static function generateReport(): array 
    {
        if (!self::$enabled) {
            return [];
        }
        
        $stats = self::getStats();
        $bottlenecks = self::getBottlenecks();
        $slowQueries = self::getSlowQueries();
        
        return [
            'summary' => $stats,
            'bottlenecks' => $bottlenecks,
            'slow_queries' => $slowQueries,
            'recommendations' => self::generateRecommendations($stats, $bottlenecks),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate performance recommendations
     * 
     * @param array $stats Performance statistics
     * @param array $bottlenecks Identified bottlenecks
     * @return array Recommendations
     */
    private static function generateRecommendations(array $stats, array $bottlenecks): array 
    {
        $recommendations = [];
        
        if (empty($bottlenecks)) {
            $recommendations[] = 'Performance looks good! No major issues detected.';
        } else {
            $recommendations[] = 'Consider implementing caching for frequently accessed data';
            $recommendations[] = 'Review and optimize database queries';
            $recommendations[] = 'Monitor memory usage patterns';
            $recommendations[] = 'Consider using a profiler for detailed analysis';
        }
        
        return $recommendations;
    }
    
    /**
     * Sanitize SQL query for logging
     * 
     * @param string $query SQL query
     * @return string Sanitized query
     */
    private static function sanitizeQuery(string $query): string 
    {
        // Remove sensitive data patterns
        $query = preg_replace('/password\s*=\s*[\'"][^\'"]*[\'"]/', 'password=***', $query);
        $query = preg_replace('/token\s*=\s*[\'"][^\'"]*[\'"]/', 'token=***', $query);
        
        return $query;
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Number of bytes
     * @return string Formatted string
     */
    private static function formatBytes(int $bytes): string 
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
    
    /**
     * Clear all metrics and reset monitoring
     */
    public static function reset(): void 
    {
        self::$metrics = [];
        self::$timers = [];
        self::$queries = [];
        self::$startTime = microtime(true);
        self::$startMemory = memory_get_usage(true);
    }
    
    /**
     * Enable or disable monitoring
     *
     * @param bool $enabled Whether to enable monitoring
     */
    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    /**
     * Get all recorded metrics
     *
     * @return array All metrics
     */
    public static function getAllMetrics(): array
    {
        return self::$metrics;
    }

    /**
     * Get all recorded queries
     *
     * @return array All queries
     */
    public static function getAllQueries(): array
    {
        return self::$queries;
    }
}
