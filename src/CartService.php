<?php declare(strict_types=1);

namespace Eventsourcing;

class CartService
{
    /**
     * @var SessionId
     */
    private $sessionId;

    public function __construct(SessionId $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @throws CartNotFoundException
     * @throws \Exception
     */
    public function getCartItems(): CartItemCollection
    {
        switch ($this->sessionId->asString()) {
            case 'ihgorhmtcvo3qmd5as2oi7thpf':
                $numberOfItems = 1;
                break;
            case 'has4t1glskcktjh4ujs9eet26u':
                $numberOfItems = 5;
                break;
            case '10603jjdasv8vpid64t214762l':
                $numberOfItems = 25;
                break;

            default:
                throw new CartNotFoundException();
        }

        $items = new CartItemCollection();
        for ($i = 0; $i < $numberOfItems; $i++) {
            $price = random_int(10, 9999);
            $items->add(new CartItem($i + 1, 'Product ' . random_int(0, 999), $price));
        }

        return $items;
    }
}
