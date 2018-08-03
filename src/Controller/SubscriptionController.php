<?php
/**
 * Created by PhpStorm.
 * User: Laptop06
 * Date: 03.08.2018
 * Time: 11:33
 */

namespace Acme\SyliusExamplePlugin\Controller;


use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends ResourceController
{

    public function cancelSubscription(Request $request, $id){
        $referer = $request->headers->get('referer');
        $continue = false;
        $subscriptionRepository = $this->container->get('contelizer.repository.subscription');
        $subscription = $subscriptionRepository->find($id);
        if($this->isGranted('ROLE_ADMINISTRATION_ACCESS')){
            $continue = true;
        }
        if($this->isGranted('ROLE_USER')){
            $userIdFromSubscription = $subscription->getCustomer()->getUser()->getId();
            $loggedUserId = $this->getUser()->getId();
            if($userIdFromSubscription === $loggedUserId){
                $continue = true;
            }
        }
        if(!$continue){
            $this->container->get('session')->getFlashBag()->add('error', 'Wystąpił błąd. Skontaktuj się z nami.');
            return new RedirectResponse($referer);
        }
        $result = $this->container->get('acme.syliusexampleplugin.subscription_service')->cancelSubscription($subscription);
        $this->container->get('session')->getFlashBag()->add('success', 'Anulowano subskrypcję.');
        return new RedirectResponse($referer);
    }

//    public function showAction(Request $request): Response
//    {
//        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);
//
//        $this->isGrantedOr403($configuration, ResourceActions::SHOW);
//        $subscription = $this->findOr404($configuration);
//
//        $recommendationServiceApi = $this->get('app.recommendation_service_api');
//
//        $recommendedProducts = $recommendationServiceApi->getRecommendedProducts($product);
//
//        $this->eventDispatcher->dispatch(ResourceActions::SHOW, $configuration, $product);
//
//        $view = View::create($product);
//
//        if ($configuration->isHtmlRequest()) {
//            $view
//                ->setTemplate($configuration->getTemplate(ResourceActions::SHOW . '.html'))
//                ->setTemplateVar($this->metadata->getName())
//                ->setData([
//                    'configuration' => $configuration,
//                    'metadata' => $this->metadata,
//                    'resource' => $product,
//                    'recommendedProducts' => $recommendedProducts,
//                    $this->metadata->getName() => $product,
//                ])
//            ;
//        }
//
//        return $this->viewHandler->handle($configuration, $view);
//    }
}