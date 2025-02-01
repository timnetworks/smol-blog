<?php
// Include Parsedown
if (!class_exists('Parsedown')) {
    class Parsedown {
        private static $instances = array();
        
        // Block Elements
        protected function blockHeader($line) {
            $level = strspn($line['text'], '#');
            if ($level > 0) {
                $text = trim(ltrim($line['text'], '# '));
                return "<h$level>$text</h$level>";
            }
            return $line['text'];
        }
        
        protected function blockQuote($lines) {
            $content = '';
            foreach ($lines as $line) {
                if (strlen($line) > 0) {
                    $content .= substr($line, 1);
                }
            }
            return "<blockquote>$content</blockquote>";
        }
        
        protected function blockCode($lines) {
            $content = implode("\n", $lines);
            return "<pre><code>" . htmlspecialchars($content) . "</code></pre>";
        }
        
        protected function processList($lines, $ordered = false) {
            $html = $ordered ? '<ol>' : '<ul>';
            foreach ($lines as $line) {
                $content = $ordered ? 
                    ltrim(substr($line, strpos($line, '.') + 1)) :
                    ltrim(substr($line, 2));
                $html .= "<li>" . $this->inlineEmphasis($content) . "</li>";
            }
            $html .= $ordered ? '</ol>' : '</ul>';
            return $html;
        }
        
        protected function inlineEmphasis($text) {
            // Code spans
            $text = preg_replace('/`(.*?)`/', '<code>$1</code>', $text);
            // Bold
            $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
            // Italic
            $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
            // Underline
            $text = preg_replace('/__(.*?)__/', '<u>$1</u>', $text);
            return $text;
        }
        
        public function text($text) {
            $lines = explode("\n", $text);
            $html = '';
            
            $i = 0;
            while ($i < count($lines)) {
                $line = $lines[$i];
                $trimmed = rtrim($line);
                
                // Skip empty lines
                if (empty($trimmed)) {
                    $html .= "<p></p>\n";
                    $i++;
                    continue;
                }
                
                $lineData = array('text' => $trimmed);
                
                // Code blocks
                if (strpos($trimmed, '```') === 0) {
                    $codeLines = [];
                    $i++;
                    while ($i < count($lines) && strpos($lines[$i], '```') !== 0) {
                        $codeLines[] = $lines[$i];
                        $i++;
                    }
                    $html .= $this->blockCode($codeLines) . "\n";
                    $i++;
                    continue;
                }
                
                // Blockquotes
                if (strpos($trimmed, '>') === 0) {
                    $quoteLines = [];
                    while ($i < count($lines) && strpos($lines[$i], '>') === 0) {
                        $quoteLines[] = $lines[$i];
                        $i++;
                    }
                    $html .= $this->blockQuote($quoteLines) . "\n";
                    continue;
                }
                
                // Unordered lists
                if (preg_match('/^[\*\-]\s/', $trimmed)) {
                    $listLines = [];
                    while ($i < count($lines) && preg_match('/^[\*\-]\s/', rtrim($lines[$i]))) {
                        $listLines[] = $lines[$i];
                        $i++;
                    }
                    $html .= $this->processList($listLines, false) . "\n";
                    continue;
                }
                
                // Ordered lists
                if (preg_match('/^\d+\.\s/', $trimmed)) {
                    $listLines = [];
                    while ($i < count($lines) && preg_match('/^\d+\.\s/', rtrim($lines[$i]))) {
                        $listLines[] = $lines[$i];
                        $i++;
                    }
                    $html .= $this->processList($listLines, true) . "\n";
                    continue;
                }
                
                // Headers
                if (strpos($trimmed, '#') === 0) {
                    $html .= $this->blockHeader($lineData) . "\n";
                } else {
                    // Process inline elements and wrap in paragraph
                    $processed = $this->inlineEmphasis($trimmed);
                    $html .= "<p>$processed</p>\n";
                }
                
                $i++;
            }
            
            return $html;
        }
        
        public static function instance($name = 'default') {
            if (!isset(self::$instances[$name])) {
                self::$instances[$name] = new static();
            }
            return self::$instances[$name];
        }
    }
}

$parsedown = new Parsedown();

// Function to read and parse blog files
function getBlogPosts($directory = './posts/', $page = 1, $posts_per_page = 10) {
    $posts = [];
    
    // Check if directory exists
    if (!is_dir($directory)) {
        return ['posts' => [], 'total_pages' => 0];
    }
    
    // Get all files matching our date pattern
    $files = glob($directory . '*.md');
    
    foreach ($files as $file) {
        // Get filename without extension and directory
        $date = basename($file, '.md');
        
        // Only process files that match our date format (YYYYMMDD)
        if (preg_match('/^[0-9]{8}$/', $date)) {
            $content = file_get_contents($file);
            // Parse the date for display
            $formatted_date = date('F j, Y', strtotime(
                substr($date, 0, 4) . '-' . 
                substr($date, 4, 2) . '-' . 
                substr($date, 6, 2)
            ));
            
            $posts[] = [
                'date' => $date,
                'formatted_date' => $formatted_date,
                'content' => $content,
                'permalink' => $directory . $date . '.md'
            ];
        }
    }
    
    // Sort posts by date in reverse chronological order
    usort($posts, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    
    // Calculate pagination
    $total_posts = count($posts);
    $total_pages = ceil($total_posts / $posts_per_page);
    $page = max(1, min($page, $total_pages)); // Ensure page is within valid range
    
    // Slice array for current page
    $offset = ($page - 1) * $posts_per_page;
    $posts = array_slice($posts, $offset, $posts_per_page);
    
    return [
        'posts' => $posts,
        'total_pages' => $total_pages
    ];
}

// Get current page from query string
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$result = getBlogPosts('./posts/', $current_page);
$blog_posts = $result['posts'];
$total_pages = $result['total_pages'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>standard-issue weblog</title>
    <meta name="title" content=" weblog">
    <meta name="description" content="standard-issue weblog">
    <meta property="og:url" content="https://www.website.com/blog">
    <meta property="og:type" content="website">
    <meta property="og:title" content="weblog">
    <meta property="og:description" content="standard-issue weblog">
    <meta property="og:image" content="https://www.website.com/opengraph.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="www.website.com">
    <meta property="twitter:url" content="https://www.website.com/blog">
    <meta name="twitter:title" content="weblog">
    <meta name="twitter:description" content="standard-issue weblog">
    <meta name="twitter:image" content="https://www.website.com/opengraph.png">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #f5f5f5;
        }
        
        header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        h1 {
            color: #333;
            margin: 0;
        }
        
        .subtitle {
            color: #666;
            font-size: 1.2rem;
            margin-top: 0.5rem;
        }
        
        hr {
            border: 0;
            height: 1px;
            background: #ddd;
            margin: 2rem 0;
        }
        
        article {
            background: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .post-date {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .post-date a {
            color: #666;
            text-decoration: none;
        }
        
        .post-date a:hover {
            text-decoration: underline;
        }
        
        img {
            max-width: 100%;
            height: auto;
        }
        
        .pagination {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 4px;
            background: white;
            color: #333;
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .pagination .current {
            background: #333;
            color: white;
        }
        
        .pagination a:hover {
            background: #eee;
        }

        /* Markdown Styles */
        .post-content h1 { font-size: 2em; margin: 0.67em 0; }
        .post-content h2 { font-size: 1.5em; margin: 0.75em 0; }
        .post-content h3 { font-size: 1.17em; margin: 0.83em 0; }
        .post-content h4 { margin: 1.12em 0; }
        .post-content h5 { font-size: 0.83em; margin: 1.5em 0; }
        .post-content h6 { font-size: 0.75em; margin: 1.67em 0; }
        .post-content strong { font-weight: bold; }
        .post-content em { font-style: italic; }
        .post-content u { text-decoration: underline; }
        
        .post-content blockquote {
            border-left: 4px solid #ddd;
            margin: 1.5em 0;
            padding: 0.5em 1em;
            color: #666;
            background: #f9f9f9;
        }
        
        .post-content pre {
            background: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 1em;
            overflow-x: auto;
        }
        
        .post-content code {
            background: #f4f4f4;
            padding: 0.2em 0.4em;
            border-radius: 3px;
            font-family: monospace;
        }
        
        .post-content pre code {
            background: none;
            padding: 0;
        }
        
        .post-content ul, .post-content ol {
            margin: 1em 0;
            padding-left: 2em;
        }
        
        .post-content li {
            margin: 0.5em 0;
        }
    </style>
</head>
<body>
    <header>
        <h1>weblog</h1>
        <div class="subtitle">standard issue weblog</div>
    </header>
    
    <hr>
    
    <main>
        <?php if (empty($blog_posts)): ?>
            <p>nothing found. did you upload or create a post?</p>
        <?php else: ?>
            <?php foreach ($blog_posts as $post): ?>
                <article>
                    <div class="post-date">
                        <a href="<?php echo htmlspecialchars($post['permalink']); ?>">
                            <?php echo htmlspecialchars($post['formatted_date']); ?>
                        </a>
                    </div>
                    <div class="post-content">
                        <?php echo $parsedown->text($post['content']); ?>
                    </div>
                </article>
            <?php endforeach; ?>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <hr>

    <newpost>
        <center><p>This project is on <a href="https://github.com/timnetworks/smol-blog">Github</a> under the MIT license. | <a href="./newpost.php">New post.</a></p></center>
    </newpost>
</body>
</html>
