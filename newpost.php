<?php
// Configuration
$password_hash = "5f4dcc3b5aa765d61d8327deb882cf99"; // Default: "password"
$posts_dir = "./posts/";

// Initialize variables
$message = "";
$authorized = false;

// Create posts directory if it doesn't exist
if (!is_dir($posts_dir)) {
    mkdir($posts_dir, 0755, true);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Password check
    if (isset($_POST['password'])) {
        if (md5($_POST['password']) === $password_hash) {
            $authorized = true;
        } else {
            $message = "Invalid password";
        }
    }
    
    // Handle post submission
    if ($authorized && isset($_POST['content'])) {
        $today = date("Ymd");
        $filename = $posts_dir . $today . ".md";
        $content = trim($_POST['content']);
        
        if (!empty($content)) {
            // Check if file exists and handle accordingly
            if (file_exists($filename)) {
                // Append to existing file with a separator
                $content = "\n\n---\n\n" . $content;
                if (file_put_contents($filename, $content, FILE_APPEND)) {
                    $message = "Content appended to today's post successfully";
                } else {
                    $message = "Error appending to file";
                }
            } else {
                // Create new file
                if (file_put_contents($filename, $content)) {
                    $message = "New post created successfully";
                } else {
                    $message = "Error creating file";
                }
            }
        } else {
            $message = "Content cannot be empty";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Blog Post</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>post weblog</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$authorized): ?>
            <form method="POST">
                <div>
                    <label for="password">auth:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div>
                    <button type="submit">login</button>
                </div>
            </form>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password']); ?>">
                <div>
                    <textarea id="content" name="content" required></textarea><br />
                    <label for="content">markdown supported:</label>
                </div>
                <div>
                    <button type="submit">post</button>
                </div>
            </form>
            
            <div class="help">
                <h4>markdown help</h4>
                <ul>
                    <li><code># Header 1</code> through <code>###### Header 6</code> for headers</li>
                    <li><code>**bold**</code> for <strong>bold text</strong></li>
                    <li><code>*italic*</code> for <em>italic text</em></li>
                    <li><code>__underline__</code> for <u>underlined text</u></li>
                    <li><code>* item</code> or <code>- item</code> for bullet lists</li>
                    <li><code>1. item</code> for numbered lists</li>
                    <li><code>```</code> for code blocks</li>
                    <li><code>`code`</code> for inline code</li>
                    <li><code>> text</code> for blockquotes</li>
                </ul>
            </div>

            <hr>

            Go back to <a href="https://timnetworks.net/blog">the main page</a>.
        <?php endif; ?>
    </div>
</body>
</html>
