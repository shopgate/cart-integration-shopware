<?php
/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

namespace Shopgate\Components;

use Shopgate\Helpers\WebCheckout;

class Favorites
{
    /**
     * @var WebCheckout
     */
    protected $webCheckoutHelper;

    /**
     * Reference to Shopware db object (Shopware()->Db)
     *
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected $db;

    /**
     * Reference to Shopware container object (Shopware()->Container)
     *
     * @var Container
     */
    protected $container;

    /**
     * Reference to Shopware session object (Shopware()->Session)
     *
     * @var Enlight_Components_Session_Namespace
     */
    protected $session;

    /**
     * Favorites constructor.
     */
    public function __construct()
    {
        $this->webCheckoutHelper = new WebCheckout();
        $this->session           = Shopware()->Session();
        $this->db                = Shopware()->Db();
        $this->container         = Shopware()->Container();
    }

    /**
     * Custom favoriteList action to create a favorite list
     *
     * @param \Enlight_Controller_Request_Request $request
     * @param \Enlight_Controller_Response_Response $response
     *
     * @return array
     */
    public function createFavoriteList($request, $response)
    {
        if ($this->isWishList() === false) {
            return array('success' => false);
        }

        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params  = $this->webCheckoutHelper->getJsonParams($request, $response);
        $decoded = $this->webCheckoutHelper->getJWT($params['token']);
        if (isset($decoded['error']) && $decoded['error']) {
            return $decoded;
        }

        //set customerId to session
        $this->webCheckoutHelper->getCustomerId($decoded['customerId']);
        $name = $decoded['name'];

        try {
            $wishlistId = $this->container->get('swag_advanced_cart.cart_handler')->createWishList($name);
        } catch (\Exception $error) {
            // something went wrong, probably a list with that name already exists
            return array('success' => false);
        }

        if ($wishlistId) {
            return array('success' => true, 'id' => $wishlistId);
        }

        return array('success' => false);
    }

    /**
     * Custom favoriteList action to rename a favorite list
     *
     * @param \Enlight_Controller_Request_Request $request
     * @param \Enlight_Controller_Response_Response $response
     *
     * @return array
     */
    public function renameFavoriteList($request, $response)
    {
        if ($this->isWishList() === false) {
            return array('success' => false);
        }

        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params  = $this->webCheckoutHelper->getJsonParams($request, $response);
        $decoded = $this->webCheckoutHelper->getJWT($params['token']);
        if (isset($decoded['error']) && $decoded['error']) {
            return $decoded;
        }
        $customerId = $this->webCheckoutHelper->getCustomerId($decoded['customerId']);
        $wishListId = $decoded['wishlistId'];
        $name = $decoded['name'];

        $wishList = $this->getWishlistById($customerId, $wishListId, false);

        if ($wishList === null) {
            return array('success' => false);
        }

        $wishList->setName($name);
        $wishList->setModified(date('Y-m-d H:i:s'));
        Shopware()->Models()->flush();

        return array('success' => true);
    }

    /**
     * Custom favoriteList action to delete a favorite list
     *
     * @param \Enlight_Controller_Request_Request $request
     * @param \Enlight_Controller_Response_Response $response
     *
     * @return array
     */
    public function deleteFavoriteList($request, $response)
    {
        if ($this->isWishList() === false) {
            return array('success' => false);
        }

        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params  = $this->webCheckoutHelper->getJsonParams($request, $response);
        $decoded = $this->webCheckoutHelper->getJWT($params['token']);
        if (isset($decoded['error']) && $decoded['error']) {
            return $decoded;
        }
        $customerId = $this->webCheckoutHelper->getCustomerId($decoded['customerId']);
        $wishListId = $decoded['wishlistId'];

        $sql = 'DELETE FROM s_order_basket_saved WHERE id = :wishlistId; AND user_id = :userId;';
        $this->container->get('db')->executeQuery($sql, [
            'wishlistId' => $wishListId,
            'userId' => $customerId
        ]);

        return array('success' => true);
    }

    /**
     * Custom favoriteList action to get the favorite lists of a user
     *
     * @param \Enlight_Controller_Request_Request $request
     *
     * @return array
     */
    public function getFavoriteLists($request)
    {
        if ($this->isWishList() === false) {
            return array('success' => false);
        }
        $decoded = $this->webCheckoutHelper->getJWT($request->getCookie('token'));
        if (isset($decoded['error']) && $decoded['error']) {
            return $decoded;
        }
        $customerId = $this->webCheckoutHelper->getCustomerId($decoded['customer_id']);

        $wishlists = [];
        foreach ($this->getWishlists($customerId) as $wishlist) {
            $wishlists[] = [
                'id' => $wishlist->getId(),
                'name' => $wishlist->getName()
            ];
        }

        return $wishlists;
    }

    /**
     * Custom favorite action to get the favorite list of an user
     *
     * @param \Enlight_Controller_Request_Request $request
     *
     * @return array
     */
    public function getFavorites($request)
    {
        $decoded = $this->webCheckoutHelper->getJWT($request->getCookie('token'));
        if (isset($decoded['error']) && $decoded['error']) {
            return $decoded;
        }
        $customerId = $this->webCheckoutHelper->getCustomerId($decoded['customer_id']);

        $nodes = Shopware()->Modules()->Basket()->sGetNotes();

        if ($this->isWishList()) {
            $wishListId = $decoded['wishlist_id'];
            $wishlist = $this->getWishlistById($customerId, $wishListId);
            $nodes = $this->prepareCartItems($wishlist);
        }

        $nodesLight = [];
        foreach ($nodes as $node) {
            $nodesLight[] = [
                'articleID' => $node['articleID'],
                'ordernumber' => $node['ordernumber'],
                'sConfigurator' => (bool) $node['sConfigurator']
            ];
        }

        return $nodesLight;
    }

    /**
     * Custom favorite action to add a product to the favorite list of an user
     *
     * @param \Enlight_Controller_Request_Request $request
     * @param \Enlight_Controller_Response_Response $response
     *
     * @return array
     */
    public function addToFavoriteList($request, $response)
    {
        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params  = $this->webCheckoutHelper->getJsonParams($request, $response);
        $decoded = $this->webCheckoutHelper->getJWT($params['token']);
        if (isset($decoded['error']) && $decoded['error']) {
            return $decoded;
        }
        $customerId = $this->webCheckoutHelper->getCustomerId($decoded['customerId']);
        $wishListId = $decoded['wishlistId'];
        $wishlist = null;
        if ($this->isWishList()) {
            $wishlist = $this->getWishlistById($customerId, $wishListId);
        }
        if ($this->addArticleToWishList($decoded['articles'], $customerId, $request, $wishlist)) {
            return array('success' => true);
        }

        return array('success' => false);
    }

    /**
     * Custom favorite action to delete a product from the favorite list of an user
     *
     * @param \Enlight_Controller_Request_Request $request
     * @param \Enlight_Controller_Response_Response $response
     *
     * @return array
     */
    public function deleteFromFavoriteList($request, $response)
    {
        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params  = $this->webCheckoutHelper->getJsonParams($request, $response);
        $decoded = $this->webCheckoutHelper->getJWT($params['token']);
        if (isset($decoded['error']) && $decoded['error']) {
            return $decoded;
        }
        $customerId = $this->webCheckoutHelper->getCustomerId($decoded['customerId']);
        $wishListId = $decoded['wishlistId'];

        $wishlist = null;
        if ($this->isWishList()) {
            $wishlist = $this->getWishlistById($customerId, $wishListId);
        }
        if ($this->removeProductFromWishList($decoded['articles'], $customerId, $wishlist)) {
            return array('success' => true);
        }

        return array('success' => false);
    }

    /**
     * A helper function to check if the wish list from the advanced cart plugin is active
     *
     * @return bool
     */
    private function isWishList()
    {
        try {
            $pluginManager = $this->container->get('shopware_plugininstaller.plugin_manager');
            $plugin        = $pluginManager->getPluginByName('SwagAdvancedCart');
            $config        =
                $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('SwagAdvancedCart');

            return $plugin->getInstalled() && $plugin->getActive() && $config['replaceNote'];
        } catch (\Exception $error) {
            return false;
        }
    }

    /**
     * @param $userId int
     * @param $wishlistId int
     * @param $fallback bool
     * @return \SwagAdvancedCart\Models\Cart\Cart|null
     */
    private function getWishlistById($userId, $wishlistId, $fallback = true)
    {
        $wishlists = $this->getWishlists($userId);
        foreach ($wishlists as $wishlist) {
            if ($wishlist->getId() === $wishlistId) {
                return $wishlist;
            }
        }

        return $fallback ? $wishlists[0] : null;
    }

    /**
     * A helper function to get the carts of an user
     *
     * @param int $userId
     *
     * @return null|\SwagAdvancedCart\Models\Cart\Cart[]
     */
    private function getWishlists($userId)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('cart', 'items', 'details'))
                ->from('SwagAdvancedCart\Models\Cart\Cart', 'cart')
                ->leftJoin('cart.customer', 'customer')
                ->leftJoin('cart.cartItems', 'items')
                ->leftJoin('items.details', 'details')
                ->where('customer.id = :customerId')
                ->andWhere('cart.shopId = :shopId')
                ->andWhere('LENGTH(cart.name) > 0')
                ->orderBy('items.id', 'DESC')
                ->setParameter('customerId', $userId)
                ->setParameter('shopId', $this->container->get('shop')->getId());

        return $builder->getQuery()->getResult();
    }

    /**
     * A helper function that prepares a single cart
     *
     * @param \SwagAdvancedCart\Models\Cart\Cart|null $cart
     *
     * @return null|array
     */
    private function prepareCartItems($cart)
    {
        $items = array();

        if ($cart === null) {
            return $items;
        }

        /** @var \SwagAdvancedCart\Models\Cart\CartItem $cartItem */
        foreach ($cart->getCartItems() as $cartItem) {
            if (!$cartItem->getDetail() || !$cartItem->getDetail()->getActive()) {
                continue;
            }
            $orderNumber = $cartItem->getProductOrderNumber();
            $product     = $this->prepareArticle($orderNumber, $cartItem);

            if ($product) {
                $items[] = $product;
            }
        }

        return $items;
    }

    /**
     * A helper function that returns a prepared product object ready to be displayed in the frontend
     *
     * @param int $orderNumber
     * @param \SwagAdvancedCart\Models\Cart\CartItem $cartItem
     *
     * @return null|array
     */
    private function prepareArticle($orderNumber, $cartItem)
    {
        if (!$orderNumber) {
            return null;
        }

        //Gets the product by the order number
        $productId = Shopware()->Modules()->Articles()->sGetArticleIdByOrderNumber($orderNumber);

        if (!$productId) {
            return null;
        }

        try {
            $product = Shopware()->Modules()->Articles()->sGetArticleById($productId, null, $orderNumber);
        } catch (\RuntimeException $e) {
            // if product is not found the ProductNumberService will throw an exception
            return null;
        }

        if (!$product || !$this->existsInMainCategory($productId)) {
            return null;
        }

        $sql             = 'SELECT active FROM s_articles_details WHERE ordernumber = :orderNumber;';
        $isVariantActive = $this->container->get('db')->fetchOne($sql, array('orderNumber' => $orderNumber));

        if (!$isVariantActive) {
            $message = $this->container->get('snippets')->getNamespace('frontend/plugins/swag_advanced_cart/plugin')
                                       ->get('ArticleNotAvailable');
            $items[] = array(
                'id'          => $cartItem->getId(),
                'ordernumber' => $cartItem->getProductOrderNumber(),
                'quantity'    => $cartItem->getQuantity(),
                'name'        => $message,
                'article'     => array(
                    'articleName' => $message,
                    'articlename' => $message,
                ),
            );

            return null;
        }

        $product['price'] = str_replace(',', '.', $product['price']) * $cartItem->getQuantity();

        //For compatibility issues
        $product['articlename'] = $product['articleName'];

        if ($product['sConfigurator']) {
            if ($product['additionaltext']) {
                $product['articlename'] .= ' ' . $product['additionaltext'];
                $product['articleName'] .= ' ' . $product['additionaltext'];
            } else {
                $productName            = Shopware()->Modules()->Articles()->sGetArticleNameByOrderNumber($orderNumber);
                $product['articlename'] = $productName;
                $product['articleName'] = $productName;
            }
        }

        $product['img'] = $product['image']['src'][0];

        return $product;
    }

    /**
     * A helper function that to add articles to wish list or note
     *
     * @param array $articles
     * @param int $userId
     * @param \Enlight_Controller_Request_Request $request
     * @param \SwagAdvancedCart\Models\Cart\Cart|null $wishlist
     *
     * @return bool
     */
    private function addArticleToWishList($articles, $userId, $request, $wishlist)
    {
        foreach ($articles as $article) {
            $articleId   = trim($article['product_id']);
            $orderNumber = trim($article['variant_id']);

            $product = Shopware()->Modules()->Articles()->sGetArticleById($articleId);

            if ($product) {
                if ($orderNumber === "") {
                    $orderNumber = $product['ordernumber'];
                }

                if ($wishlist) {
                    if ($wishlist === null) {
                        $request->setPost('newlist', 'App');
                    } else {
                        $request->setPost('lists', array($wishlist->getId()));
                    }

                    $request->setPost('ordernumber', $orderNumber);
                    $this->container->get('swag_advanced_cart.cart_handler')->addToList($request->getPost());
                } else {
                    $productId   = Shopware()->Modules()->Articles()->sGetArticleIdByOrderNumber($orderNumber);
                    $productName = Shopware()->Modules()->Articles()->sGetArticleNameByOrderNumber($orderNumber);

                    if (empty($productId)) {
                        return false;
                    }

                    Shopware()->Modules()->Basket()->sAddNote($productId, $productName, $orderNumber);
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * A helper function that to delete articles from wish list or note list
     *
     * @param array $articles
     * @param int   $userId
     * @param \SwagAdvancedCart\Models\Cart\Cart|null $wishlist
     *
     * @return bool
     */
    private function removeProductFromWishList($articles, $userId, $wishlist)
    {
        foreach ($articles as $article) {
            $articleId   = trim($article['product_id']);
            $orderNumber = trim($article['variant_id']);

            $product = Shopware()->Modules()->Articles()->sGetArticleById($articleId);

            if ($product) {
                if ($orderNumber === "") {
                    $orderNumber = $product['ordernumber'];
                }

                if ($wishlist) {
                    $builder = Shopware()->Models()->createQueryBuilder();
                    $builder->select('item')
                            ->from('SwagAdvancedCart\Models\Cart\CartItem', 'item')
                            ->where('item.productOrderNumber = :itemId')
                            ->andWhere('item.basket_id = :basketId')
                            ->innerJoin('item.cart', 'cart')
                            ->innerJoin('cart.customer', 'customer')
                            ->andWhere('customer.id = :userId')
                            ->setParameter('itemId', $orderNumber)
                            ->setParameter('basketId', $wishlist->getId())
                            ->setParameter('userId', $userId);

                    $cartItem = $builder->getQuery()->getOneOrNullResult();

                    if (empty($cartItem)) {
                        return false;
                    }

                    $wishlist->setModified(date('Y-m-d H:i:s'));

                    $modelManager = Shopware()->Models();
                    $modelManager->remove($cartItem);
                    $modelManager->flush();
                } else {
                    $delete = Shopware()->Db()->query(
                        'DELETE FROM s_order_notes 
                        WHERE userID = ? AND ordernumber = ?',
                        array((int)$userId, $orderNumber)
                    );

                    if (!$delete) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check if product exists in active category
     *
     * @param $productId
     *
     * @return mixed
     */
    private function existsInMainCategory($productId)
    {
        $categoryId = Shopware()->Shop()->getCategory()->getId();

        $exist = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_articles_categories_ro WHERE categoryID = ? AND articleID = ?',
            array($categoryId, $productId)
        );

        return $exist;
    }
}
