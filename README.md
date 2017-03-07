# MockWithExpectationsTrait

This trait contains some helper methods to be used in a sub-class of
`PHPUnit\Framework\TestCase`.

I like testing with mocks and expectations.
It lets me test my code without having to test the components with which it interacts.
Those components need to be tested too, but not by each test I write of code that uses them.

Consider this `refundOrder()` function:
```
public function refundOrder($orderId)
{
    $order = $this->orderRepository->findOrder($orderId);
    if ($order->isRefundable()) {
        $this->orderRefunder->refund($order);
    }
}
```

Here is a PHPUnit test for `refundOrder()`:
```
public function testRefundOrder()
{
    $orderId = 1337;
    $order = $this->createMock(Order::class);
    $orderRepository = $this->createMock(OrderRepository::class);
    $orderRefunder = $this->createMock(OrderRefunder::class);
    
    $orderRepository->expects($this->once())
        ->method('findOrder')
        ->with($orderId)
        ->willReturn($order);
     
    $order->expects($this->once())
        ->method('isRefundable')
        ->willReturn(true);
        
    $orderRefunder->expects($this->once())
        ->method('refund')
        ->with($order);
        
    $manager = new OrderManager($orderRepository, $orderRefunder);
    
    $manager->refundOrder($orderId);
}
```

Refactoring `refundOrder()`:

```
public function refundOrder($orderId)
{
    $order = $this->findOrder($orderId);
    $this->refund($order);
}

protected function findOrder($orderId) : Order
{
    return $this->orderRepository->findOrder($orderId);
}

protected function isRefundable(Order $order) : bool
{
    return $order->isRefundable();
}

protected function refund(Order $order)
{
    return $this->orderRefunder->refund($order);
}
```

And using `MockWithExpectationsTrait`:
```
public function testRefundOrder()
{
    $orderId = 1337;
    $order = $this->createMock(Order::class);
    $manager = $this->newPartialMockWithExpectations(OrderManager::class, [
        ['findOrder', ['params' => [$orderId], 'result' => $order],
        ['isRefundable', ['params' => [$order], 'result' => true]
        ['refund', ['params' => [$order]]],
    ]);
    $manager->refundOrder($orderId);
}

public function testRefundOrderNotRefundable()
{
    $orderId = 1337;
    $order = $this->createMock(Order::class);
    $manager = $this->newPartialMockWithExpectations(OrderManager::class, [
        ['findOrder', ['params' => [$orderId], 'result' => $order],
        ['isRefundable', ['params' => [$order], 'result' => false]
        ['refund', 'never'],
    ]);
    $manager->refundOrder($orderId);
}
```

Discuss.