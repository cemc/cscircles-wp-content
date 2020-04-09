def digitalSum(n):
    if not isinstance(n, int) or n < 0:
        raise(TypeError("digitalSum can only be applied to non-negative integers"))
        
    if (n < 10): return n
    return n % 10 + digitalSum(n // 10)

