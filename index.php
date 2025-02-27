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
    <title>timnetworks weblog</title>
    <meta name="title" content="timnetworks weblog">
    <meta name="description" content="standard-issue weblog by timnetworks corporation">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="title" content="timnetworks weblog">
    <meta name="description" content="standard-issue weblog by timnetworks corporation">
    <meta property="og:url" content="https://www.blog.timnetworks.net">
    <meta property="og:type" content="website">
    <meta property="og:title" content="timnetworks weblog">
    <meta property="og:description" content="standard-issue weblog by timnetworks corporation">
    <meta property="og:image" content="https://blog.timnetworks.net/opengraph.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="www.blog.timnetworks.net">
    <meta property="twitter:url" content="https://www.blog.timnetworks.net">
    <meta name="twitter:title" content="timnetworks weblog">
    <meta name="twitter:description" content="standard-issue weblog by timnetworks corporation">
    <meta name="twitter:image" content="https://blog.timnetworks.net/opengraph.png">
    <meta name="twitter:site" content="@timnetworkscorp"/>
    <meta name="robots" content="index, follow"/>
    <style>
    /* Base colors */
    :root {
        --bg-primary: #FFFEF1;
        --bg-secondary: #F5F4E8;
        --bg-tertiary: #ECEADD;
        --text-primary: #2C3E50;
        --text-secondary: #546E7A;
        --accent-primary: #B58900;
        --accent-secondary: #268BD2;
        --border-color: #E6E4D1;
        --code-bg: #F7F6E9;
        --blockquote-bg: #FAFAF2;
    }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        line-height: 1.6;
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem;
        background: var(--bg-primary);
        color: var(--text-primary);
        position: relative;
    }

    header {
        text-align: center;
        margin-bottom: 2rem;
    }

    h1 {
        color: var(--accent-primary);
        margin: 0;
    }

    .subtitle {
        color: var(--text-secondary);
        font-size: 1.2rem;
        margin-top: 0.5rem;
    }

    hr {
        border: 0;
        height: 1px;
        background: var(--border-color);
        margin: 2rem 0;
    }

    article {
        background: var(--bg-secondary);
        padding: 2rem;
        margin-bottom: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
    }

    .post-date {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .post-date a {
        color: var(--accent-secondary);
        text-decoration: none;
    }

    .post-date a:hover {
        text-decoration: underline;
    }
       
    img {
            max-width: 100%;
            height: auto;
        }

    /* Pagination styles */
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
        background: var(--bg-secondary);
        color: var(--text-primary);
        text-decoration: none;
        border: 1px solid var(--border-color);
    }

    .pagination .current {
        background: var(--accent-primary);
        color: var(--bg-primary);
        border-color: var(--accent-primary);
    }

    .pagination a:hover {
        background: var(--bg-tertiary);
    }

    /* Markdown Content Styles */
    .post-content h1, .post-content h2, .post-content h3, 
    .post-content h4, .post-content h5, .post-content h6 {
        color: var(--accent-primary);
    }

    .post-content a {
        color: var(--accent-secondary);
        text-decoration: none;
    }

    .post-content a:hover {
        text-decoration: underline;
    }

    .post-content blockquote {
        border-left: 4px solid var(--accent-primary);
        margin: 1.5em 0;
        padding: 0.5em 1em;
        color: var(--text-secondary);
        background: var(--blockquote-bg);
    }

    .post-content pre {
        background: var(--code-bg);
        border: 1px solid var(--border-color);
        border-radius: 4px;
        padding: 1em;
        overflow-x: auto;
    }

    .post-content code {
        background: var(--code-bg);
        padding: 0.2em 0.4em;
        border-radius: 3px;
        font-family: monospace;
        color: var(--accent-primary);
    }

    .post-content pre code {
        background: none;
        padding: 0;
        color: var(--text-primary);
    }

    /* New Post Form Styles */
    .container {
        background: var(--bg-secondary);
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
    }

    input[type="password"], textarea {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }

    button {
        background: var(--accent-primary);
        color: var(--bg-primary);
        border: none;
        transition: background-color 0.2s;
    }

    button:hover {
        background: #946e00;
    }

    .message {
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .message.error {
        background: #fdf2f2;
        color: #c53030;
        border: 1px solid #feb2b2;
    }

    .message.success {
        background: #f0fff4;
        color: #2f855a;
        border: 1px solid #9ae6b4;
    }

    .help {
        border-top: 1px solid var(--border-color);
    }

    /* Dropdown Menu Styles */
    .dropdown {
        position: absolute;
        top: 1rem;
        right: 2rem;
        z-index: 100;
    }

    .dropdown-btn {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.5rem 1rem;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        font-size: 0.9rem;
    }

    .dropdown-btn:hover {
        background: var(--bg-tertiary);
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background: var(--bg-secondary);
        min-width: 200px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        border-radius: 4px;
        border: 1px solid var(--border-color);
        padding: 0.5rem 0;
        z-index: 1;
    }

    .dropdown-content a {
        color: var(--text-primary);
        padding: 0.5rem 1rem;
        text-decoration: none;
        display: block;
    }

    .dropdown-content a:hover {
        background: var(--bg-tertiary);
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    .dropdown-copyright {
        padding: 0.5rem 1rem;
        color: var(--text-secondary);
        font-size: 0.8rem;
        border-top: 1px solid var(--border-color);
        margin-top: 0.5rem;
    }
    </style>
</head>
<body>
    <div class="dropdown">
        <button class="dropdown-btn">Menu &#9662;</button>
        <div class="dropdown-content">
            <a href="https://github.com/timnetworks/smol-blog">Github</a>
            <a href="./newpost.php">New post</a>
            <a href="https://timnetworks.com">timnetworks</a>
            <div class="dropdown-copyright">&copy; 2025 timnetworks corporation.</div>
        </div>
    </div>
    
    <header>
        <h1>weblog</h1>
        <div class="subtitle">nothing to like (or subscribe to)</div>
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
    <p>♥️ Thanks for visiting.</p>
</body>
</html>
