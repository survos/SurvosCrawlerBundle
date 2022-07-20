<?php


namespace Survos\CrawlerBundle\Model;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use function Symfony\Component\String\u;

class Link
{
    public const STATUS_IGNORED = 'ignored';

    public function __construct(public string $path,
                                public $seen = false,
                                public int $depth=0,
                                public ?string $linkStatus = null,
                                public ?string $username=null,

                                public ?string $route = null,
                                public ?array $rp = null,
                                private ?string $html = null,
                                private ?float $duration = null,
                                public ?int $statusCode = null,
                                public ?string $foundOn=null)
    {
    }

    /**
     * @return string|null
     */
    public function getLinkStatus(): ?string
    {
        return $this->linkStatus;
    }

    /**
     * @param string|null $linkStatus
     * @return Link
     */
    public function setLinkStatus(?string $linkStatus): Link
    {
        $this->linkStatus = $linkStatus;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRoute(): ?string
    {
        return $this->route;
    }

    /**
     * @param string|null $route
     * @return Link
     */
    public function setRoute(?string $route): Link
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getRp(): ?array
    {
        return $this->rp;
    }

    /**
     * @param array|null $rp
     * @return Link
     */
    public function setRp(?array $rp): Link
    {
        $this->rp = $rp;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHtml(): ?string
    {
        return $this->html;
    }

    /**
     * @param string|null $html
     * @return Link
     */
    public function setHtml(?string $html): Link
    {
        $this->html = $html;
        return $this;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @param int $depth
     * @return Link
     */
    public function setDepth(int $depth): Link
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getDuration(): ?float
    {
        return $this->duration;
    }

    /**
     * @param float|null $duration
     * @return Link
     */
    public function setDuration(?float $duration): Link
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @param int|null $statusCode
     * @return Link
     */
    public function setStatusCode(?int $statusCode): Link
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFoundOn(): ?string
    {
        return $this->foundOn;
    }

    /**
     * @param string|null $foundOn
     * @return Link
     */
    public function setFoundOn(?string $foundOn): Link
    {
        $this->foundOn = $foundOn;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getSeen(): bool
    {
        return $this->seen;
    }

    public function setSeen(bool $seen): self
    {
        $this->seen = $seen;
        return $this;
    }

    public function isPending(): bool
    {
        return !$this->getSeen();
    }

    public function testable(): bool
    {
        return $this->getSeen() && $this->getLinkStatus() <> self::STATUS_IGNORED;
    }

}
