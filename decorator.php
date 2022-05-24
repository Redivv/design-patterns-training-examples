<?php

/**
 * The Component interface declares a filtering method that must be implemented
 * by all concrete components and decorators.
 */
interface InputFormat
{
    public function formatText(string $text): string;
}

/**
 * The Concrete Component is a core element of decoration. It contains the
 * original text, as is, without any filtering or formatting.
 */
class TextInput implements InputFormat
{
    public function formatText(string $text): string
    {
        return $text;
    }
}

/**
 * The base Decorator class doesn't contain any real filtering or formatting
 * logic. Its main purpose is to implement the basic decoration infrastructure:
 * a field for storing a wrapped component or another decorator and the basic
 * formatting method that delegates the work to the wrapped object. The real
 * formatting job is done by subclasses.
 */
class TextFormat implements InputFormat
{

    public function __construct(protected InputFormat $inputFormat)
    {
    }

    /**
     * Decorator delegates all work to a wrapped component.
     */
    public function formatText(string $text): string
    {
        return $this->inputFormat->formatText($text);
    }
}

/**
 * This Concrete Decorator strips out all HTML tags from the given text.
 */
class PlainTextFilter extends TextFormat
{
    public function formatText(string $text): string
    {
        $text = parent::formatText($text);
        return strip_tags($text);
    }
}

/**
 * This Concrete Decorator strips only dangerous HTML tags and attributes that
 * may lead to an XSS vulnerability.
 */
class DangerousHTMLTagsFilter extends TextFormat
{
    private $dangerousTagPatterns = [
        "|<script.*?>([\s\S]*)?</script>|i", // ...
    ];

    private $dangerousAttributes = [
        "onclick", "onkeypress", // ...
    ];


    public function formatText(string $text): string
    {
        $text = parent::formatText($text);

        foreach ($this->dangerousTagPatterns as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }

        foreach ($this->dangerousAttributes as $attribute) {
            $text = preg_replace_callback('|<(.*?)>|', function ($matches) use ($attribute) {
                $result = preg_replace("|$attribute=|i", '', $matches[1]);
                return "<" . $result . ">";
            }, $text);
        }

        return $text;
    }
}

/**
 * This Concrete Decorator provides a rudimentary Markdown → HTML conversion.
 */
class MarkdownFormat extends TextFormat
{
    public function formatText(string $text): string
    {
        $text = parent::formatText($text);

        // Format block elements.
        $chunks = preg_split('|\n<br>\n<br>|', $text);
        foreach ($chunks as &$chunk) {
            // Format headers.
            if (preg_match('|^#+|', $chunk)) {
                $chunk = preg_replace_callback('|^(#+)(.*?)$|', function ($matches) {
                    $h = strlen($matches[1]);
                    return "<h$h>" . trim($matches[2]) . "</h$h>";
                }, $chunk);
            } // Format paragraphs.
            else {
                $chunk = "<p>$chunk</p>";
            }
        }
        $text = implode("\n<br>\n<br>", $chunks);

        // Format inline elements.
        $text = preg_replace("|__(.*?)__|", '<strong>$1</strong>', $text);
        $text = preg_replace("|\*\*(.*?)\*\*|", '<strong>$1</strong>', $text);
        $text = preg_replace("|_(.*?)_|", '<em>$1</em>', $text);
        $text = preg_replace("|\*(.*?)\*|", '<em>$1</em>', $text);

        return $text;
    }
}


/**
 * The client code might be a part of a real website, which renders user-
 * generated content. Since it works with formatters through the Component
 * interface, it doesn't care whether it gets a simple component object or a
 * decorated one.
 */
function displayCommentAsAWebsite(InputFormat $format, string $text)
{
    // ..

    echo $format->formatText($text);

    // ..
}

/**
 * Input formatters are very handy when dealing with user-generated content.
 * Displaying such content "as is" could be very dangerous, especially when
 * anonymous users can generate it (e.g. comments). Your website is not only
 * risking getting tons of spammy links but may also be exposed to XSS attacks.
 */
$dangerousComment = <<<HERE
Hello! Nice blog post!
Please visit my <a href='http://www.iwillhackyou.com'>homepage</a>.
<script src="http://www.iwillhackyou.com/script.js">
  performXSSAttack();
</script>
HERE;

/**
 * Naive comment rendering (unsafe).
 */
$naiveInput = new TextInput();
echo "Website renders comments without filtering (unsafe):\n<br>";
displayCommentAsAWebsite($naiveInput, $dangerousComment);
echo "\n<br>\n<br>\n<br>";

/**
 * Filtered comment rendering (safe).
 */
$filteredInput = new PlainTextFilter($naiveInput);
echo "Website renders comments after stripping all tags (safe):\n<br>";
displayCommentAsAWebsite($filteredInput, $dangerousComment);
echo "\n<br>\n<br>\n<br>";


/**
 * Decorator allows stacking multiple input formats to get fine-grained control
 * over the rendered content.
 */
$dangerousForumPost = <<<HERE
# Welcome

This is my first post on this **gorgeous** forum.

<script src="http://www.iwillhackyou.com/script.js">
  performXSSAttack();
</script>
HERE;

/**
 * Naive post rendering (unsafe, no formatting).
 */
$naiveInput = new TextInput();
echo "Website renders a forum post without filtering and formatting (unsafe, ugly):\n<br>";
displayCommentAsAWebsite($naiveInput, $dangerousForumPost);
echo "\n<br>\n<br>\n<br>";

/**
 * Markdown formatter + filtering dangerous tags (safe, pretty).
 */
$text = new TextInput();
$markdown = new MarkdownFormat($text);
$filteredInput = new DangerousHTMLTagsFilter($markdown);
echo "Website renders a forum post after translating markdown markup" .
    " and filtering some dangerous HTML tags and attributes (safe, pretty):\n<br>";
displayCommentAsAWebsite($filteredInput, $dangerousForumPost);
echo "\n<br>\n<br>\n<br>";