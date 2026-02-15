<?php

namespace App\Controller;

use App\Entity\Product;
use App\Response\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {}

    /**
     * Get all products for authenticated user
     */
    #[Route('', name: 'api_products_list', methods: ['GET'])]
    public function list(): ApiResponse
    {
        $user = $this->getUser();
        
        $products = $this->em->getRepository(Product::class)
            ->findBy(['user' => $user], ['id' => 'DESC']);

        $data = array_map(function (Product $product) {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'created_at' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $products);

        return ApiResponse::success(
            data: $data,
            message: 'Products retrieved successfully'
        );
    }

    /**
     * Get single product by ID
     */
    #[Route('/{id}', name: 'api_products_show', methods: ['GET'])]
    public function show(int $id): ApiResponse
    {
        $user = $this->getUser();
        
        $product = $this->em->getRepository(Product::class)
            ->findOneBy(['id' => $id, 'user' => $user]);

        if (!$product) {
            return ApiResponse::notFound(
                message: 'Product not found',
                errors: ['product' => 'Product does not exist or you do not have access']
            );
        }

        return ApiResponse::success(
            data: [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'created_at' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
            ],
            message: 'Product retrieved successfully'
        );
    }

    /**
     * Create new product
     */
    #[Route('', name: 'api_products_create', methods: ['POST'])]
    public function create(Request $request): ApiResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        // Validate input
        $errors = $this->validateProductData($data);
        if (!empty($errors)) {
            return ApiResponse::validationError(
                errors: $errors,
                message: 'Validation failed'
            );
        }

        // Create product
        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setUser($user);

        $this->em->persist($product);
        $this->em->flush();

        return ApiResponse::created(
            data: [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
            ],
            message: 'Product created successfully'
        );
    }

    /**
     * Update existing product
     */
    #[Route('/{id}', name: 'api_products_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): ApiResponse
    {
        $user = $this->getUser();
        
        $product = $this->em->getRepository(Product::class)
            ->findOneBy(['id' => $id, 'user' => $user]);

        if (!$product) {
            return ApiResponse::notFound(
                message: 'Product not found',
                errors: ['product' => 'Product does not exist or you do not have access']
            );
        }

        $data = json_decode($request->getContent(), true);

        // Validate input
        $errors = $this->validateProductData($data, false);
        if (!empty($errors)) {
            return ApiResponse::validationError(
                errors: $errors,
                message: 'Validation failed'
            );
        }

        // Update fields
        if (isset($data['name'])) {
            $product->setName($data['name']);
        }
        if (isset($data['price'])) {
            $product->setPrice($data['price']);
        }

        $this->em->flush();

        return ApiResponse::success(
            data: [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
            ],
            message: 'Product updated successfully'
        );
    }

    /**
     * Delete product
     */
    #[Route('/{id}', name: 'api_products_delete', methods: ['DELETE'])]
    public function delete(int $id): ApiResponse
    {
        $user = $this->getUser();
        
        $product = $this->em->getRepository(Product::class)
            ->findOneBy(['id' => $id, 'user' => $user]);

        if (!$product) {
            return ApiResponse::notFound(
                message: 'Product not found',
                errors: ['product' => 'Product does not exist or you do not have access']
            );
        }

        $this->em->remove($product);
        $this->em->flush();

        return ApiResponse::success(
            data: null,
            message: 'Product deleted successfully'
        );
    }

    /**
     * Validate product data
     */
    private function validateProductData(array $data, bool $requireAll = true): array
    {
        $errors = [];

        // Validate name
        if ($requireAll && !isset($data['name'])) {
            $errors['name'] = 'Product name is required';
        } elseif (isset($data['name'])) {
            if (empty(trim($data['name']))) {
                $errors['name'] = 'Product name cannot be empty';
            } elseif (strlen($data['name']) > 255) {
                $errors['name'] = 'Product name cannot exceed 255 characters';
            }
        }

        // Validate price
        if ($requireAll && !isset($data['price'])) {
            $errors['price'] = 'Product price is required';
        } elseif (isset($data['price'])) {
            if (!is_numeric($data['price'])) {
                $errors['price'] = 'Price must be a valid number';
            } elseif ($data['price'] < 0) {
                $errors['price'] = 'Price cannot be negative';
            } elseif ($data['price'] > 99999999.99) {
                $errors['price'] = 'Price is too large';
            }
        }

        return $errors;
    }
}