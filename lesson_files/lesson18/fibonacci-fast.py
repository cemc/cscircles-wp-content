def Fibonacci(n):
    sequence = [0, 1, 1]  # Fibonacci(0) is 0, Fibonacci(1) and Fibonacci(2) are 1
    for i in range(3, n+1):      
        sequence.append(sequence[i-1] + sequence[i-2])
    return sequence[n]
