<?php

/**
 * The Abstraction.
 */
abstract class Page
{
    /**
     * The Abstraction is usually initialized with one of the Implementation
     * objects.
     */
    public function __construct(protected Renderer $renderer)
    {
    }

    /**
     * The Bridge pattern allows replacing the attached Implementation object
     * dynamically.
     */
    public function changeRenderer(Renderer $renderer): void
    {
        $this->renderer = $renderer;
    }

    /**
     * The "view" behavior stays abstract since it can only be provided by
     * Concrete Abstraction classes.
     */
    abstract public function view(): string;
}

/**
 * This Concrete Abstraction represents a simple page.
 */
class SimplePage extends Page
{
    public function __construct(Renderer $renderer, protected string $title, protected string $content)
    {
        parent::__construct($renderer);
    }

    public function view(): string
    {
        return $this->renderer->renderParts([
            $this->renderer->renderHeader(),
            $this->renderer->renderTitle($this->title),
            $this->renderer->renderTextBlock($this->content),
            $this->renderer->renderFooter()
        ]);
    }
}

/**
 * This Concrete Abstraction represents a more complex page.
 */
class ProductPage extends Page
{
    public function __construct(Renderer $renderer, protected Product $product)
    {
        parent::__construct($renderer);
    }

    public function view(): string
    {
        return $this->renderer->renderParts([
            $this->renderer->renderHeader(),
            $this->renderer->renderTitle($this->product->getTitle()),
            $this->renderer->renderTextBlock($this->product->getDescription()),
            $this->renderer->renderImage($this->product->getImage()),
            $this->renderer->renderLink("/cart/add/" . $this->product->getId(), "Add to cart"),
            $this->renderer->renderFooter()
        ]);
    }
}

/**
 * A helper class for the ProductPage class.
 */
class Product
{

    public function __construct(
        private string $id,
        private string $title,
        private string $description,
        private string $image,
        private float $price
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}


/**
 * The Implementation declares a set of "real", "under-the-hood", "platform"
 * methods.
 *
 * In this case, the Implementation lists rendering methods that can be used to
 * compose any web page. Different Abstractions may use different methods of the
 * Implementation.
 */
interface Renderer
{
    public function renderTitle(string $title): string;

    public function renderTextBlock(string $text): string;

    public function renderImage(string $url): string;

    public function renderLink(string $url, string $title): string;

    public function renderHeader(): string;

    public function renderFooter(): string;

    public function renderParts(array $parts): string;
}

/**
 * This Concrete Implementation renders a web page as HTML.
 */
class HTMLRenderer implements Renderer
{
    public function renderTitle(string $title): string
    {
        return "<h1>$title</h1>";
    }

    public function renderTextBlock(string $text): string
    {
        return "<div class='text'>$text</div>";
    }

    public function renderImage(string $url): string
    {
        return "<img src='$url'>";
    }

    public function renderLink(string $url, string $title): string
    {
        return "<a href='$url'>$title</a>";
    }

    public function renderHeader(): string
    {
        return "<html><body>";
    }

    public function renderFooter(): string
    {
        return "</body></html>";
    }

    public function renderParts(array $parts): string
    {
        return implode("\n", $parts);
    }
}

/**
 * This Concrete Implementation renders a web page as JSON strings.
 */
class JsonRenderer implements Renderer
{
    public function renderTitle(string $title): string
    {
        return '"title": "' . $title . '"';
    }

    public function renderTextBlock(string $text): string
    {
        return '"text": "' . $text . '"';
    }

    public function renderImage(string $url): string
    {
        return '"img": "' . $url . '"';
    }

    public function renderLink(string $url, string $title): string
    {
        return '"link": {"href": "' . $url . '", "title": "' . $title . '"}';
    }

    public function renderHeader(): string
    {
        return '';
    }

    public function renderFooter(): string
    {
        return '';
    }

    public function renderParts(array $parts): string
    {
        return "{\n<br>" . implode(",\n", array_filter($parts)) . "\n<br>}";
    }
}

/**
 * The client code usually deals only with the Abstraction objects.
 */
function clientCode(Page $page)
{
    // ...

    echo $page->view();

    // ...
}

/**
 * The client code can be executed with any pre-configured combination of the
 * Abstraction+Implementation.
 */
$HTMLRenderer = new HTMLRenderer();
$JSONRenderer = new JsonRenderer();

$page = new SimplePage($HTMLRenderer, "Home", "Welcome to our website!");
echo "HTML view of a simple content page:\n<br>";
clientCode($page);
echo "\n\n<br><br>";

/**
 * The Abstraction can change the linked Implementation at runtime if needed.
 */
$page->changeRenderer($JSONRenderer);
echo "JSON view of a simple content page, rendered with the same client code:\n<br>";
clientCode($page);
echo "\n\n<br><br>";


$product = new Product(
    "123",
    "Star Wars, episode1",
    "A long time ago in a galaxy far, far away...",
    "/images/star-wars.jpeg",
    39.95
);

$page = new ProductPage($HTMLRenderer, $product);
echo "HTML view of a product page, same client code:\n<br>";
clientCode($page);
echo "\n\n<br><br>";

$page->changeRenderer($JSONRenderer);
echo "JSON view of a simple content page, with the same client code:\n<br>";
clientCode($page);
