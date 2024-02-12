<?php

namespace App\Entity;

use App\Repository\AdminRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=AdminRepository::class)
 */
class Admin implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="boolean", options={"default": "0"})
     */
    private $isSuperAdmin;

    /**
     * @ORM\Column(type="boolean", options={"default": "0"})
     */
    private $isEditor;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity=ImportLog::class, mappedBy="admin", orphanRemoval=true)
     */
    private $importLogs;

    /**
     * @ORM\OneToMany(targetEntity=Product::class, mappedBy="editor")
     */
    private $products;

    public function __construct()
    {
        $this->setIsSuperAdmin(false);
        $this->setIsEditor(false);
        $this->importLogs = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_ADMIN';

        if ($this->getIsEditor()) {
            $roles[] = 'ROLE_EDITOR';
        }

        if ($this->getIsSuperAdmin()) {
            $roles[] = 'ROLE_SUPER_ADMIN';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsSuperAdmin()
    {
        return $this->isSuperAdmin;
    }

    /**
     * @param mixed $isSuperAdmin
     */
    public function setIsSuperAdmin($isSuperAdmin): void
    {
        $this->isSuperAdmin = $isSuperAdmin;
    }

    public function getIsEditor()
    {
        return $this->isEditor;
    }

    public function setIsEditor($isEditor): self
    {
        $this->isEditor = $isEditor;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|ImportLog[]
     */
    public function getImportLogs(): Collection
    {
        return $this->importLogs;
    }

    public function addImportLog(ImportLog $importLog): self
    {
        if (!$this->importLogs->contains($importLog)) {
            $this->importLogs[] = $importLog;
            $importLog->setAdmin($this);
        }

        return $this;
    }

    public function removeImportLog(ImportLog $importLog): self
    {
        if ($this->importLogs->removeElement($importLog)) {
            // set the owning side to null (unless already changed)
            if ($importLog->getAdmin() === $this) {
                $importLog->setAdmin(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getEmail();
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setEditor($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getEditor() === $this) {
                $product->setEditor(null);
            }
        }

        return $this;
    }
}
