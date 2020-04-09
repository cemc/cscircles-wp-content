def Fibonacci(n):
    sequence = [0, 1, 1]  # Fibonacci(0)输出0, Fibonacci(1)和Fibonacci(2)输出1
    for i in range(3, n+1):      
        sequence.append(sequence[i-1] + sequence[i-2])
    return sequence[n]
