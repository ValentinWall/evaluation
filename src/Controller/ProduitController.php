<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProduitController extends AbstractController
{
    private $managerRegistry;
    private $produitRepository;

    public function __construct(ManagerRegistry $managerRegistry, ProduitRepository $produitRepository) //ce constructeur permet d'alléger le code, si jamais je devais appeller plusieurs fois ManagerRegistry et ProduitRepository
    {
        $this->managerRegistry = $managerRegistry;
        $this->produitRepository = $produitRepository;
    }

    #[Route('/', name: 'produit')]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $produitRepository->findBy([], ['id' => 'DESC'], 15) //affiche 15 premier produits et par ordre antéchronologique
        ]);
    }

    #[Route('/produit/create/{title}', name: 'produit_show')]
    public function show(ProduitRepository $produitRepository, string $title): Response
    {
        
        return $this->render('produit/show.html.twig', [
            'produit' => $produitRepository->findOneBy(['title' => $title]) //Sélectionne chaque produit par leur titre
        ]);
    }

    #[Route('/produit/create', name: 'produit_create')]
    public function create(Request $request): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $infoImg = $form['img']->getData(); 

            if (empty($infoImg)) {
                return $this->redirectToRoute('produit_create');
            } elseif (empty($form['alt']->getData())) {
                return $this->redirectToRoute('produit_create');
            }

            $imgName = time() . '-1.' . $infoImg->guessExtension();
            $infoImg->move($this->getParameter('product_img_dir'), $imgName); //transfère l'image du produit dans le dossier "img/product"
            $produit->setImg($imgName);

            $manager = $this->managerRegistry->getManager();
            $manager->persist($produit);
            $manager->flush();

            $this->addFlash('success', 'Le produit a bien été créé'); //le message qui s'affiche lorsque le produit est créé
            return $this->redirectToRoute('produit');
        }

        return $this->render('produit/form.html.twig', [
            'produitForm' => $form->createView() // Permet de mettre en vue le formulaire de création
        ]);
    }
}
