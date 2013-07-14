import socket
UDP_IP = "129.97.140.241" # cemclinux1 -- talking to self should be blocked
#UDP_IP = "173.194.73.105" # this is google
UDP_PORT = 5005
MESSAGE = "Hello, World!"
sock = socket.socket( socket.AF_INET, socket.SOCK_DGRAM )
for i in range(0, 100):
    print(sock.sendto( bytes(MESSAGE, 'utf8') , (UDP_IP, UDP_PORT) ))
