<?php
// Configuration
$password_hash = "5f4dcc3b5aa765d61d8327deb882cf99"; // Default: "password"
$posts_dir = "./posts/";
$img_dir = "./posts/img/";

// Initialize variables
$message = "";
$authorized = false;
$current_post = "";
$current_file = "";

// Create necessary directories if they don't exist
if (!is_dir($posts_dir)) {
    mkdir($posts_dir, 0755, true);
}
if (!is_dir($img_dir)) {
    mkdir($img_dir, 0755, true);
}

// Get today's date for default file
$today = date("Ymd");
$today_file = $posts_dir . $today . ".md";

// Function to get all post files
function getPosts($dir) {
    $files = glob($dir . "*.md");
    // Sort in reverse chronological order (newest first)
    rsort($files);
    return $files;
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
    
    // Handle text post submission
    if ($authorized && isset($_POST['content']) && isset($_POST['file'])) {
        $filename = $_POST['file'];
        // Validate filename is in posts directory
        if (strpos($filename, $posts_dir) === 0 && pathinfo($filename, PATHINFO_EXTENSION) === 'md') {
            $content = trim($_POST['content']);
            
            if (!empty($content)) {
                if (file_put_contents($filename, $content)) {
                    $message = "Post saved successfully";
                    $current_file = $filename;
                    $current_post = $content;
                } else {
                    $message = "Error saving file";
                }
            } else {
                $message = "Content cannot be empty";
            }
        } else {
            $message = "Invalid file path";
        }
    }
    
    // Handle image upload
    if ($authorized && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_name = basename($_FILES['image']['name']);
            $unique_name = time() . '_' . $file_name;
            $upload_path = $img_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Process with ImageMagick if available
                if (extension_loaded('imagick')) {
                    try {
                        $image = new Imagick($upload_path);
                        $width = $image->getImageWidth();
                        
                        // Resize if width is greater than 1280px
                        if ($width > 1280) {
                            $image->resizeImage(1280, 0, Imagick::FILTER_LANCZOS, 1);
                        }
                        
                        // Set quality and convert to JPG
                        $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                        $image->setImageCompressionQuality(90);
                        $image->setFormat('jpg');
                        
                        // Save the compressed image
                        $new_name = pathinfo($unique_name, PATHINFO_FILENAME) . '.jpg';
                        $compressed_path = $img_dir . $new_name;
                        $image->writeImage($compressed_path);
                        $image->clear();
                        $image->destroy();
                        
                        // Remove original if it's different from compressed
                        if ($upload_path != $compressed_path) {
                            unlink($upload_path);
                        }
                        
                        $message = "Image uploaded and processed successfully. Use: ![Alt text](/posts/img/" . $new_name . ")";
                        $img_path = "/posts/img/" . $new_name;
                    } catch (Exception $e) {
                        $message = "Error processing image with ImageMagick. Basic upload succeeded.";
                        $img_path = "/posts/img/" . $unique_name;
                    }
                } else {
                    $message = "ImageMagick not available. Basic upload succeeded.";
                    $img_path = "/posts/img/" . $unique_name;
                }
            } else {
                $message = "Error uploading file";
            }
        } else {
            $message = "Only JPG, PNG, and GIF files are allowed";
        }
    }
    
    // Handle file selection
    if ($authorized && isset($_POST['select_file'])) {
        $selected_file = $_POST['select_file'];
        // Validate filename is in posts directory
        if (strpos($selected_file, $posts_dir) === 0 && pathinfo($selected_file, PATHINFO_EXTENSION) === 'md') {
            $current_file = $selected_file;
            $current_post = file_exists($current_file) ? file_get_contents($current_file) : "";
        } else {
            $message = "Invalid file path";
        }
    }
}

// If authorized and no file is selected yet, default to today's file
if ($authorized && empty($current_file)) {
    $current_file = $today_file;
    $current_post = file_exists($current_file) ? file_get_contents($current_file) : "";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Blog Posts</title>
    <style>
    /* Base colors - Solarized */
    :root {
        --bg-primary: #FDF6E3;
        --bg-secondary: #EEE8D5;
        --bg-tertiary: #E0DBB7;
        --text-primary: #586E75;
        --text-secondary: #93A1A1;
        --accent-primary: #B58900;
        --accent-secondary: #268BD2;
        --border-color: #D4CDB4;
        --code-bg: #F8F2DC;
        --blockquote-bg: #F7F0D4;
    }
    
    * {
        box-sizing: border-box;
    }
    
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        line-height: 1.6;
        margin: 0;
        padding: 0;
        background: var(--bg-primary);
        color: var(--text-primary);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1rem;
    }

    h1 {
        color: var(--accent-primary);
        margin: 0;
        text-align: center;
        padding: 1rem 0;
    }

    /* Responsive layout */
    .editor-layout {
        display: flex;
        flex-direction: row;
        gap: 1rem;
    }

    @media (max-width: 768px) {
        .editor-layout {
            flex-direction: column;
        }
    }

    /* Post list panel */
    .post-list {
        flex: 0 0 250px;
        background: var(--bg-secondary);
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
        max-height: 80vh;
        overflow-y: auto;
    }

    @media (max-width: 768px) {
        .post-list {
            flex: auto;
            max-height: 200px;
        }
    }

    .post-list h3 {
        margin-top: 0;
        color: var(--accent-secondary);
    }

    .post-list ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .post-list li {
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--border-color);
    }

    .post-list button {
        background: none;
        border: none;
        color: var(--text-primary);
        cursor: pointer;
        width: 100%;
        text-align: left;
        padding: 0.5rem;
        border-radius: 4px;
    }

    .post-list button:hover {
        background: var(--bg-tertiary);
    }

    .post-list button.active {
        background: var(--accent-secondary);
        color: var(--bg-primary);
    }

    .post-list .create-new {
        margin-top: 1rem;
        background: var(--accent-primary);
        color: var(--bg-primary);
        padding: 0.5rem;
        text-align: center;
        border-radius: 4px;
    }

    /* Editor panel */
    .editor-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .editor-container {
        background: var(--bg-secondary);
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
    }

    textarea {
        width: 100%;
        min-height: 50vh;
        padding: 1rem;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        line-height: 1.6;
        resize: vertical;
    }

    /* Image upload panel */
    .image-upload {
        background: var(--bg-secondary);
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
    }

    /* Markdown help */
    .markdown-help {
        background: var(--bg-secondary);
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
    }

    .markdown-help-toggle {
        background: none;
        border: none;
        color: var(--accent-secondary);
        cursor: pointer;
        padding: 0;
        font-size: 1rem;
        display: flex;
        align-items: center;
    }

    .markdown-help-content {
        display: none;
        margin-top: 1rem;
    }

    .markdown-help-content.show {
        display: block;
    }

    .markdown-help table {
        width: 100%;
        border-collapse: collapse;
    }

    .markdown-help th, .markdown-help td {
        border: 1px solid var(--border-color);
        padding: 0.5rem;
    }

    .markdown-help th {
        background: var(--bg-tertiary);
    }

    /* General form elements */
    input[type="password"], input[type="file"] {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        padding: 0.5rem;
        border-radius: 4px;
        color: var(--text-primary);
        width: 100%;
        margin-bottom: 1rem;
    }

    button, input[type="submit"] {
        background: var(--accent-primary);
        color: var(--bg-primary);
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    button:hover, input[type="submit"]:hover {
        background: #946e00;
    }

    .save-button {
        background: var(--accent-secondary);
        margin-top: 1rem;
    }

    .save-button:hover {
        background: #1a6aa8;
    }

    /* Message box */
    .message {
        padding: 1rem;
        margin: 1rem 0;
        border-radius: 4px;
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

    /* Login form */
    .login-form {
        max-width: 400px;
        margin: 0 auto;
        background: var(--bg-secondary);
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>edit weblog</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false || strpos($message, 'Invalid') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$authorized): ?>
            <div class="login-form">
                <form method="POST">
                    <div>
                        <label for="password">auth:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div>
                        <button type="submit">login</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="editor-layout">
                <!-- Post list sidebar -->
                <div class="post-list">
                    <h3>Entries</h3>
                    <ul>
                        <?php
                        $posts = getPosts($posts_dir);
                        foreach ($posts as $post) {
                            $filename = basename($post);
                            $date = substr($filename, 0, 8);
                            $formatted_date = date('Y-m-d', strtotime($date));
                            $is_active = ($post == $current_file);
                            
                            echo '<li>
                                <form method="POST">
                                    <input type="hidden" name="password" value="' . htmlspecialchars($_POST['password']) . '">
                                    <input type="hidden" name="select_file" value="' . htmlspecialchars($post) . '">
                                    <button type="submit" class="' . ($is_active ? 'active' : '') . '">' . 
                                        htmlspecialchars($formatted_date) . 
                                    '</button>
                                </form>
                            </li>';
                        }
                        ?>
                    </ul>
                    
                    <?php if (!file_exists($today_file)): ?>
                    <div class="create-new">
                        <form method="POST">
                            <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password']); ?>">
                            <input type="hidden" name="select_file" value="<?php echo htmlspecialchars($today_file); ?>">
                            <button type="submit">Create Today's Entry</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Editor and tools -->
                <div class="editor-panel">
                    <div class="editor-container">
                        <form method="POST">
                            <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password']); ?>">
                            <input type="hidden" name="file" value="<?php echo htmlspecialchars($current_file); ?>">
                            
                            <textarea id="content" name="content" required><?php echo htmlspecialchars($current_post); ?></textarea>
                            
                            <button type="submit" class="save-button">Save</button>
                        </form>
                    </div>
                    
                    <!-- Image upload section -->
                    <div class="image-upload">
                        <h3>Upload Image</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password']); ?>">
                            <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif" required>
                            <button type="submit">Upload</button>
                        </form>
                        <p class="note">JPG, PNG and GIF only. Images will be compressed to max 1280px width.</p>
                        
                        <?php if (!empty($img_path)): ?>
                        <div class="image-preview">
                            <p>Image uploaded to: <?php echo htmlspecialchars($img_path); ?></p>
                            <p>Markdown: <code>![Alt text](<?php echo htmlspecialchars($img_path); ?>)</code></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Markdown help section -->
                    <div class="markdown-help">
                        <button class="markdown-help-toggle" onclick="toggleMarkdownHelp()">
                            <span>Markdown Help</span>
                            <span id="toggle-icon">▼</span>
                        </button>
                        
                        <div class="markdown-help-content" id="markdown-help">
                            <table>
                                <tr>
                                    <th>Format</th>
                                    <th>Syntax</th>
                                </tr>
                                <tr>
                                    <td>Headers</td>
                                    <td><code># Header 1</code> through <code>###### Header 6</code></td>
                                </tr>
                                <tr>
                                    <td>Bold</td>
                                    <td><code>**bold text**</code></td>
                                </tr>
                                <tr>
                                    <td>Italic</td>
                                    <td><code>*italic text*</code></td>
                                </tr>
                                <tr>
                                    <td>Underline</td>
                                    <td><code>__underlined text__</code></td>
                                </tr>
                                <tr>
                                    <td>Link</td>
                                    <td><code>[link text](URL)</code></td>
                                </tr>
                                <tr>
                                    <td>Image</td>
                                    <td><code>![alt text](image-url.jpg)</code></td>
                                </tr>
                                <tr>
                                    <td>Bullet List</td>
                                    <td><code>* item</code> or <code>- item</code></td>
                                </tr>
                                <tr>
                                    <td>Numbered List</td>
                                    <td><code>1. item</code></td>
                                </tr>
                                <tr>
                                    <td>Blockquote</td>
                                    <td><code>> text</code></td>
                                </tr>
                                <tr>
                                    <td>Code Block</td>
                                    <td><code>```<br>code<br>```</code></td>
                                </tr>
                                <tr>
                                    <td>Inline Code</td>
                                    <td><code>`code`</code></td>
                                </tr>
                                <tr>
                                    <td>Horizontal Rule</td>
                                    <td><code>---</code> or <code>***</code></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div>
                        <a href="https://timnetworks.net/blog">Return to Blog</a>
                    </div>
                </div>
            </div>
            
            <script>
                function toggleMarkdownHelp() {
                    const helpContent = document.getElementById('markdown-help');
                    const toggleIcon = document.getElementById('toggle-icon');
                    
                    if (helpContent.classList.contains('show')) {
                        helpContent.classList.remove('show');
                        toggleIcon.textContent = '▼';
                    } else {
                        helpContent.classList.add('show');
                        toggleIcon.textContent = '▲';
                    }
                }
                
                // Auto-size the textarea based on content
                const textarea = document.getElementById('content');
                if (textarea) {
                    textarea.addEventListener('input', function() {
                        this.style.height = 'auto';
                        this.style.height = (this.scrollHeight) + 'px';
                    });
                    
                    // Initial sizing
                    textarea.style.height = 'auto';
                    textarea.style.height = (textarea.scrollHeight) + 'px';
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
