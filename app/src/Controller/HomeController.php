<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $query      = trim($request->query->get('q', ''));
        $categoryRaw = $request->query->get('category');
        $categoryId = ($categoryRaw !== null && $categoryRaw !== '') ? (int) $categoryRaw : null;
        $minRaw     = $request->query->get('min_price');
        $maxRaw     = $request->query->get('max_price');
        $minPrice   = ($minRaw !== null && $minRaw !== '') ? (float) $minRaw : null;
        $maxPrice   = ($maxRaw !== null && $maxRaw !== '') ? (float) $maxRaw : null;

        $products   = $productRepository->findByFilters($query, $categoryId, $minPrice, $maxPrice);
        $categories = $categoryRepository->findAll();

        return $this->render('home/index.html.twig', [
            'products'   => $products,
            'categories' => $categories,
            'filters'    => [
                'q'         => $query,
                'category'  => $categoryId,
                'min_price' => $minPrice ?? '',
                'max_price' => $maxPrice ?? '',
            ],
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_show')]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
    
}