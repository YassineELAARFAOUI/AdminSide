<?php
// src/Controller/LoginAdminController.php

namespace App\Controller;

use App\Form\LoginAdminType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LoginAdminController extends AbstractController
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/loginAdmin', name: 'loginAdmin', methods: ['GET', 'POST'])]
    public function loginAdmin(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créez un nouveau formulaire de login
        $form = $this->createForm(LoginAdminType::class);
        $form->handleRequest($request);

        // Si la requête est GET, affichez simplement le formulaire
        if ($request->isMethod('GET')) {
            return $this->render('task/new.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        // Si la requête est POST, vérifiez si le formulaire est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Authentification et gestion de la session utilisateur (code à ajouter ici)
            $data = $form->getData();
            
            try {
                // Appel à l'API distante pour l'authentification
                $response = $this->httpClient->request('POST', 'https://127.0.0.1:8000/loginClient', [
                    'json' => [
                        'email' => $data['email'],
                        'password' => $data['password']
                    ],
                    'verify_peer' => false,  // Désactiver la vérification du certificat SSL
                    'verify_host' => false,  // Désactiver la vérification de l'hôte SSL
                ]);

                $statusCode = $response->getStatusCode();
                $content = $response->toArray(false); // Désactiver l'exception sur 4xx/5xx
                if ($content['isBlocked']) {
                    // Authentification réussie
                    return new JsonResponse(['login' => 0,'isBlocked' => 1]);
                } 
                if ($content['password'] === $data['password']) {
                    // Authentification réussie
                    return new JsonResponse(['login' => 1,'content'=>$content,'message' => 'Authentification réussie']);
                } else {
                    // Authentification échouée
                    return new JsonResponse(['login' => 0,'error' =>  'Email et password sont incorrect']);
                }
            } catch (\Exception $e) {
                // Capture et affiche toute exception
                return new JsonResponse(['login' => 0, 'error' => 'Erreur lors de l\'appel à l\'API : ' . $e->getMessage()]);
            }
        }

        // Si le formulaire n'est pas valide
        return new JsonResponse(['login' => 0, 'error' => 'Formulaire invalide']);
    }

    #[Route('/loginSuccess', name: 'login_success')]
    public function loginSuccess(): Response
    {
        // Rediriger vers le modèle Twig task/reponse.html.twig après une authentification réussie
        return $this->render('task/reponse.html.twig', [
            'login' => 1,
        ]);
        
        
        
    }
  // Route to get all clients by admin
  #[Route('/viewAllClientbyadmin', name: 'viewAllClientbyadmin', methods: ['GET'])]
  public function viewAllClientbyadmin(Request $request, EntityManagerInterface $entityManager): Response
  {
      try {
          // Call the remote API for authentication
          $response = $this->httpClient->request('GET', 'https://127.0.0.1:8000/viewAllclient', [
              'verify_peer' => false,  // Disable SSL certificate verification
              'verify_host' => false,  // Disable SSL host verification
          ]);

          $statusCode = $response->getStatusCode();
          $content = $response->toArray(false); // Disable exception on 4xx/5xx status

          if ($statusCode === 200 && !empty($content)) {
              // Successful authentication
              return $this->render('task/clients.html.twig', [
                'content' => $content, // $clients doit contenir la liste des clients récupérée depuis votre source de données
                'clients'=>$content['allClients'],
            ]);
          }

          // If the content is empty or status code is not 200
          return new JsonResponse([
              'content' => 0,
              'message' => 'Failed to retrieve content or invalid status code'
          ]);

      } catch (\Exception $e) {
          return new JsonResponse([
              'error' => 'Erreur lors de l\'appel à l\'API : ' . $e->getMessage()
          ]);
      }
  }
  //route to get all account bussiness by admin
  #[Route('/viewAllaccBusinessbyadmin', name: 'viewAllaccBusinessbyadmin', methods: ['GET'])]
  public function viewAllaccBusinessbyadmin(Request $request, EntityManagerInterface $entityManager): Response
  {
      try {
          $response = $this->httpClient->request('GET', 'https://127.0.0.1:8000/viewAllaccBusiness', [
              'verify_peer' => false,
              'verify_host' => false,
          ]);
  
          $statusCode = $response->getStatusCode();
          $content = $response->toArray(false);
  
          if ($statusCode === 200 && !empty($content)) {
              // Dump the content to see its structure
              dump($content);
  
              return $this->render('task/acc_business.html.twig', [
                  'content' => $content,
                  'allAccBusiness' => $content['allAccBusiness'],
              ]);
          }
  
          return new JsonResponse([
              'content' => 0,
              'message' => 'Failed to retrieve content or invalid status code'
          ]);
  
      } catch (\Exception $e) {
          return new JsonResponse([
              'error' => 'Erreur lors de l\'appel à l\'API : ' . $e->getMessage()
          ]);
      }
  }
  //////////////////////////////////////////////////////////////////
 // Route pour bloquer et débloquer un client par un administrateur
 #[Route('/blockOrDeblockClientByAdmin/{id}/{block}', name: 'blockOrDeblockClientByAdmin', methods: ['POST'])]
 public function blockOrDeblockClientByAdmin(Request $request, EntityManagerInterface $entityManager, int $id, bool $block): Response
 {
     // Validate CSRF token
     if (!$this->isCsrfTokenValid('block_client_' . $id, $request->request->get('_token'))) {
         return new JsonResponse(['state' => 0, 'error' => 'Invalid CSRF token']);
     }
 
     try {
         // Appel à l'API distante pour le blocage/déblocage
         $response = $this->httpClient->request('POST', 'https://127.0.0.1:8000/blockOrDeblockClient', [
             'json' => [
                 'id' => $id,
                 'block' => $block
             ],
             'verify_peer' => false,  // Désactiver la vérification du certificat SSL
             'verify_host' => false,  // Désactiver la vérification de l'hôte SSL
         ]);
 
         $statusCode = $response->getStatusCode();
         $content = $response->toArray(false); // Désactiver l'exception sur 4xx/5xx
 
         if ($content['state']) {
             // Opération réussie
             return $this->redirectToRoute('viewAllClientbyadmin');
         }
 
     } catch (\Exception $e) {
         // Capture et affiche toute exception
         return new JsonResponse(['error' => 'Erreur lors de l\'appel à l\'API : ' . $e->getMessage()]);
     }
 
     // Si la requête n'a pas abouti
     return new JsonResponse(['state' => 0, 'error' => 'Formulaire invalide']);
 }
 //////////////////////////////////
 // Route pour bloquer et débloquer un client par un administrateur
 #[Route('/blockOrDeblockaccBusinessByAdmin/{id}/{block}', name: 'blockOrDeblockaccBusinessByAdmin', methods: ['POST'])]
 public function blockOrDeblockaccBusinessByAdmin(Request $request, EntityManagerInterface $entityManager, int $id, bool $block): Response
 {
     // Validate CSRF token
     if (!$this->isCsrfTokenValid('accBusiness_block_' . $id, $request->request->get('_token'))) {
         return new JsonResponse(['state' => 0, 'error' => 'Invalid CSRF token']);
     }
 
     try {
         // Appel à l'API distante pour le blocage/déblocage
         $response = $this->httpClient->request('POST', 'https://127.0.0.1:8000/blockOrDeblockaccBusiness', [
             'json' => [
                 'id' => $id,
                 'block' => $block
             ],
             'verify_peer' => false,  // Désactiver la vérification du certificat SSL
             'verify_host' => false,  // Désactiver la vérification de l'hôte SSL
         ]);
 
         $statusCode = $response->getStatusCode();
         $content = $response->toArray(false); // Désactiver l'exception sur 4xx/5xx
 
         if ($content['state']) {
             // Opération réussie
             return $this->redirectToRoute('viewAllaccBusinessbyadmin');
         }
 
     } catch (\Exception $e) {
         // Capture et affiche toute exception
         return new JsonResponse(['error' => 'Erreur lors de l\'appel à l\'API : ' . $e->getMessage()]);
     }
 
     // Si la requête n'a pas abouti
     return new JsonResponse(['state' => 0, 'error' => 'Formulaire invalide']);
 }
 

    }
