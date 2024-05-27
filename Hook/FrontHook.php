<?php

/*************************************************************************************/
/*      This file is part of the GoogleTagManager package.                           */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace GoogleTagManager\Hook;


use GoogleTagManager\GoogleTagManager;
use GoogleTagManager\Service\GoogleTagService;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Template\Assets\AssetResolverInterface;
use Thelia\Model\LangQuery;
use Thelia\TaxEngine\TaxEngine;
use TheliaSmarty\Template\SmartyParser;

/**
 * Class FrontHook
 * @package GoogleTagManager\Hook
 * @author Tom Pradat <tpradat@openstudio.fr>
 */
class FrontHook extends BaseHook
{
    public function __construct(
        SmartyParser $parser,
        AssetResolverInterface $resolver,
        private readonly GoogleTagService         $googleTagService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TaxEngine                $taxEngine,
        private readonly RequestStack             $requestStack,
    ) {
        parent::__construct($parser, $resolver, $eventDispatcher);
    }

    /**
     * @throws \JsonException
     * @throws PropelException
     */
    public function onMainHeadTop(HookRenderEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        /** @var Session $session */
        $session = $request?->getSession();

        $gtmId = GoogleTagManager::getConfigValue(GoogleTagManager::GOOGLE_TAG_MANAGER_GMT_ID_CONFIG_KEY);

        if ("" !== $gtmId) {
            $view = $request?->get('_view');

            $event->add($this->render('datalayer/thelia-page-view.html', ['data' => $this->googleTagService->getTheliaPageViewParameters()]));

            if (in_array($view, ['category', 'brand', 'search'])) {
                $event->add($this->render('datalayer/view-item-list.html', ['eventName' => 'view_item_list']));
            }

            if ($view === 'product') {
                $event->add($this->render('datalayer/view-item.html', ['eventName' => 'view_item']));
            }

            if (null !== $authAction = $session->get(GoogleTagManager::GOOGLE_TAG_TRIGGER_LOGIN)) {
                $event->add($this->render('datalayer/thelia-page-view.html', [
                    'data' => $this->googleTagService->getLogInData($authAction)
                ]));
                $session->set(GoogleTagManager::GOOGLE_TAG_TRIGGER_LOGIN, null);
            }

            if ($view === 'order-delivery') {
                /** @var Session $session */
                $session = $request?->getSession();
                $cart = $session->getSessionCart($this->eventDispatcher);

                $event->add($this->render('datalayer/thelia-page-view.html', [
                    'data' => $this->googleTagService->getCartData($cart?->getId(), $this->taxEngine->getDeliveryCountry())
                ]));

                $event->add($this->render('datalayer/thelia-page-view.html', [
                    'data' => $this->googleTagService->getCheckOutData($cart?->getId(), $this->taxEngine->getDeliveryCountry())
                ]));
            }

            if ($view === 'order-placed' && $orderId = $request?->get('order_id')) {
                $event->add($this->render('datalayer/thelia-page-view.html', [
                    'data' => $this->googleTagService->getPurchaseData($orderId)
                ]));

                $event->add($this->render('datalayer/thelia-page-view.html', [
                    'data' => $this->googleTagService->getPaymentInfo($orderId)
                ]));

                $event->add($this->render('datalayer/thelia-page-view.html', [
                    'data' => $this->googleTagService->getShippingInfo($orderId)
                ]));
            }

            $event->add(
                "<!-- Google Tag Manager -->" .
                    "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':" .
                    "new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0]," .
                    "j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=" .
                    "'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);" .
                    "})(window,document,'script','dataLayer','" . $gtmId . "');</script>" .
                    "<!-- End Google Tag Manager -->"
            );
        }
    }

    public function onMainBodyTop(HookRenderEvent $event): void
    {
        if ($value = GoogleTagManager::getConfigValue(GoogleTagManager::GOOGLE_TAG_MANAGER_GMT_ID_CONFIG_KEY)) {
            $event->add(
                "<!-- Google Tag Manager (noscript) -->" .
                    "<noscript><iframe src='https://www.googletagmanager.com/ns.html?id=" . $value . "' " .
                    "height='0' width='0' style='display:none;visibility:hidden'></iframe></noscript>" .
                    "<!-- End Google Tag Manager (noscript) -->"
            );
        }
    }

    public function onMainJsInit(HookRenderEvent $event): void
    {
        $view = $this->requestStack->getCurrentRequest()?->get('_view');

        if (in_array($view, ['category', 'brand', 'search'])) {
            $event->add($this->render('datalayer/select-item.html'));
        }

        //include event listener to handle adding to cart event (check README)
        $event->add($this->render('datalayer/add-to-cart.html'));
    }

    public function onProductBottom(HookRenderEvent $event): void
    {
        $productId = $event->getArgument('product');
        $this->requestStack->getCurrentRequest()?->getSession()->set(GoogleTagManager::GOOGLE_TAG_VIEW_ITEM, $productId);
    }

    protected function getLang()
    {
        $lang = $this->getRequest()->getSession()?->get("thelia.current.lang");
        if (null === $lang) {
            $lang = LangQuery::create()->filterByByDefault(1)->findOne();
        }
        return $lang;
    }

    public static function getSubscribedHooks(): array
    {
        return [
            "main.head-top" => [
                [
                    "type" => "front",
                    "method" => "onMainHeadTop"
                ]
            ],
            "main.body-top" => [
                [
                    "type" => "front",
                    "method" => "onMainBodyTop"
                ]
            ],
            "main.javascript-initialization" => [
                [
                    "type" => "front",
                    "method" => "onMainJsInit"
                ]
            ],
            "product.bottom" => [
                [
                    "type" => "front",
                    "method" => "onProductBottom"
                ]
            ]
        ];
    }
}
