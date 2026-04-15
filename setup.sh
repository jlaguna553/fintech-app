#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}🚀 Iniciando Setup Maestro de Fintech App...${NC}"

# 1. Función para instalar Docker si no existe
install_docker() {
    echo -e "${YELLOW}🐳 Docker no detectado. Iniciando instalación...${NC}"
    sudo apt-get update
    sudo apt-get install -y ca-certificates curl gnupg
    sudo install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    sudo chmod a+r /etc/apt/keyrings/docker.gpg

    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
    $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
    sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    
    # Agregar usuario al grupo docker para no usar sudo después
    sudo usermod -aG docker $USER
    echo -e "${GREEN}✅ Docker instalado correctamente.${NC}"
    echo -e "${YELLOW}⚠️  IMPORTANTE: Debes reiniciar tu sesión (o cerrar y abrir la terminal) para aplicar los permisos de Docker.${NC}"
}

# 2. Verificar Docker
if ! [ -x "$(command -v docker)" ]; then
    install_docker
    # Si acabamos de instalar docker, el script debe detenerse aquí 
    # porque los permisos de grupo no se aplican en la misma sesión de shell.
    echo -e "${BLUE}Por favor, reinicia tu terminal y ejecuta este script de nuevo para terminar la configuración.${NC}"
    exit 1
fi

# 3. Levantar Proyecto (Igual que el anterior)
echo -e "${GREEN}📦 Docker detectado. Levantando microservicios...${NC}"
docker compose up -d --build

echo -e "${BLUE}⏳ Esperando 10 segundos a que MySQL inicie...${NC}"
sleep 10

echo -e "${GREEN}📥 Instalando dependencias PHP...${NC}"
docker exec -it user-service composer install
docker exec -it transaction-service composer install

sudo chown -R $USER:$USER .

echo -e "${GREEN}✨ ¡Ambiente listo!${NC}"
