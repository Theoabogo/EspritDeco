<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Image;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminProductController extends AbstractController
{
    #[Route('/admin/products', name: 'app_admin_product_list')]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('admin_product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/admin/products/delete/{id}', name: 'admin_product_delete')]
    public function delete(Product $product, EntityManagerInterface $em): Response
    {
        foreach ($product->getImages() as $image) {

            $filePath = $this->getParameter('kernel.project_dir')
                . '/public/'
                . $image->getPath();

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $em->remove($image);
        }

        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'Produit supprimé avec succès.');

        return $this->redirectToRoute('app_admin_product_list');
    }

    #[Route(
        '/admin/products/save/{id}',
        name: 'admin_product_save',
        requirements: ['id' => '\d+'],
        defaults: ['id' => null]
    )]
    public function save(
        ?int $id,
        Request $request,
        EntityManagerInterface $em,
        ?Product $product
    ): Response {

        if (!$product) {
            $product = new Product();
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /**
             * 🎯 Récupération des fichiers uploadés dynamiques
             * name="images[]"
             */
            $files = $request->files->get('images');

            $hasMain = count($product->getImages()) > 0;

            if ($files) {

                foreach ($files as $file) {

                    if (!$file) continue;

                    $filename = uniqid() . '.' . $file->guessExtension();

                    $file->move(
                        $this->getParameter('uploads_dir'),
                        $filename
                    );

                    $image = new Image();
                    $image->setPath('uploads/' . $filename);
                    $image->setProduct($product);
                    $image->setAlt('Image du produit ' . $product->getTitle());

                    // ⭐ première image = principale
                    if (!$hasMain) {
                        $image->setIsPrincipal(true);
                        $hasMain = true;
                    } else {
                        $image->setIsPrincipal(false);
                    }

                    $em->persist($image);
                }
            }

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit enregistré avec succès.');

            return $this->redirectToRoute('app_admin_product_list');
        }

        return $this->render('admin_product/save.html.twig', [
            'form' => $form->createView(),
            'isEdit' => $id !== null,
            'product' => $product
        ]);
    }

    #[Route('/admin/image/main/{id}', name: 'admin_image_main', methods: ['POST'])]
    public function setMainImage(
        Image $image,
        EntityManagerInterface $em
    ): Response {

        $product = $image->getProduct();

        foreach ($product->getImages() as $img) {
            $img->setIsPrincipal(false);
        }

        $image->setIsPrincipal(true);

        $em->flush();

        return $this->json([
            'success' => true,
            'id' => $image->getId()
        ]);
    }
}