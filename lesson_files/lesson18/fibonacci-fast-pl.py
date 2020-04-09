def Fibonacci(n):
    sequence = [0, 1, 1]  # Fibonacci(0) wynosi 0, Fibonacci(1) i Fibonacci(2) wynosi 1
    for i in range(3, n+1):      
        sequence.append(sequence[i-1] + sequence[i-2])
    return sequence[n]
