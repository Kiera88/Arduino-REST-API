#include <SPI.h>
#include <Ethernet.h>

int index = 0;
char messageBuffer[13];
char cmd[3];
char pin[3];
char val[4];
char response[5];

byte mac[] = { 0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xED };
IPAddress serverIP(192,168,1,222);
int port = 3000;

EthernetServer server(port);

void setup() {
  Serial.begin(9600);
  Ethernet.begin(mac, serverIP);
  server.begin();
  Serial.print("Arduino is at ");
  Serial.println(Ethernet.localIP());
}

void loop() {
  EthernetClient socketClient = server.available();
  if(socketClient){
    Serial.println("Server Connected");
    while(socketClient.connected()){
      if(socketClient.available()) {
          char x = socketClient.read();
          if (x == '\n'){
            process();
            socketClient.print(response);
            Serial.println(response);  
          }else{
            messageBuffer[index++] = x;
          }
      }
    }
    Serial.println("Server Disconnected");
    socketClient.stop();
  }
}

/*
 * Parse TCP Message
 */
void process() {
  index = 0;
  
  Serial.println(messageBuffer);
  strncpy(cmd, messageBuffer, 2);
  cmd[2] = '\0';
  
  strncpy(pin, messageBuffer + 2, 2);
  pin[2] = '\0';

  strncpy(val, messageBuffer + 4, 3);
  val[4] = '\0';

  int cmdid = atoi(cmd);

  switch(cmdid) {
    case 11:  digRead(pin);            break;
    case 12:  anRead(pin);             break;    
    case 21:  digWrite(pin,val);       break;
    case 22:  anWrite(pin,val);        break;
    default:                           break;
  }
}

/*
 * Set pin mode
 */
void pMode(char *pin, char *val) {
  int p = getPin(pin);
  if(p == -1){
    return;
  }
  if (atoi(val) == 0) {
    pinMode(p, OUTPUT);
  } else {
    pinMode(p, INPUT);
  }
}

/*
 * Digital write
 */
void digWrite(char *pin, char *val) {
  int p = getPin(pin);
  if(p == -1) {
    return;
  }
  pinMode(p, OUTPUT);
  char message[3];  
  if (atoi(val) == 0) {
    digitalWrite(p, LOW);
    sprintf(message, "%1d", 0);
    generateSuccessResponse(message);    
  } else {
    digitalWrite(p, HIGH);
    sprintf(message, "%1d", 1);
    generateSuccessResponse(message);     
  }
}

/*
 * Digital read
 */
void digRead(char *pin) {
  int p = getPin(pin);
  if(p == -1) {
    return;
  }
  pinMode(p, INPUT);
  int value = digitalRead(p);
  char message[4];
  sprintf(message, "%1d", value);
  generateSuccessResponse(message);
}

/*
 * Analog read
 */
void anRead(char *pin) {
  //Serial.println("Analog Read");
  int p = getPin(pin);
  if(p == -1) {
    return;
  }
  pinMode(p, INPUT);
  int value = analogRead(p);
  char message[5];
  sprintf(message, "%4d", value);
  generateSuccessResponse(message);
}

void anWrite(char *pin, char *val) {
  int p = getPin(pin);
  if(p == -1) { 
    return;
  }  
  pinMode(p, OUTPUT);
  analogWrite(p,atoi(val));
  int value = atoi(val);
  char message[4];
  sprintf(message, "%3d", value);
  generateSuccessResponse(message);  
}

int getPin(char *pin) {
  int ret = -1;
    ret = atoi(pin);
    if(ret == 0 && (pin[0] != '0' || pin[1] != '0')) {
      ret = -1;
    }
  return ret;
}

/*
 * Send Response to Server
 */
void generateSuccessResponse(char *message){
  strncpy(response, message, sizeof(message)+2);
}
