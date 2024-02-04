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

    public function __construct(
        public string $path,
        public $seen = false,
        public int $depth = 0,
        public ?string $linkStatus = null,
        public ?string $username = null,
        public ?string $route = null,
        public ?array $rp = null,
        private ?string $html = null,
        private ?float $duration = null,
        public ?int $statusCode = null,
        public ?string $foundOn = null,
        public ?int $memory = null
    ) {
    }

    public function getMemory(): ?int
    {
        return $this->memory;
    }

    public function setMemory(): Link
    {
        $this->memory = (int)round(memory_get_usage()/1048576,2);
        return $this;
    }

    public function getLinkStatus(): ?string
    {
        return $this->linkStatus;
    }


    public function setLinkStatus(?string $linkStatus): Link
    {
        $this->linkStatus = $linkStatus;
        return $this;
    }


    public function getRoute(): ?string
    {
        return $this->route;
    }


    public function setRoute(?string $route): Link
    {
        $this->route = $route;
        return $this;
    }


    public function getRp(): ?array
    {
        return $this->rp;
    }


    public function setRp(?array $rp): Link
    {
        $this->rp = $rp;
        return $this;
    }


    public function getHtml(): ?string
    {
        return $this->html;
    }


    public function setHtml(?string $html): Link
    {
        $this->html = $html;
        return $this;
    }


    public function getDepth(): int
    {
        return $this->depth;
    }


    public function setDepth(int $depth): Link
    {
        $this->depth = $depth;
        return $this;
    }


    public function getDuration(): ?float
    {
        return $this->duration;
    }


    public function setDuration(?float $duration): Link
    {
        $this->duration = $duration;
        return $this;
    }


    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }


    public function setStatusCode(?int $statusCode): Link
    {
        $this->statusCode = $statusCode;
        return $this;
    }


    public function getFoundOn(): ?string
    {
        return $this->foundOn;
    }


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
        return ! $this->getSeen();
    }

    public function testable(): bool
    {
        return $this->getSeen() && $this->getLinkStatus() <> self::STATUS_IGNORED;
    }
}
