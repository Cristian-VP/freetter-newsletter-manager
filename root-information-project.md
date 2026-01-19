





## Freetter
Una plataforma newsletter open
source, 100% gratuita, Carbon
tracking integrado y transparencia
radical.



## Cristian Andrés Vacacela Procel
FP Intensivo en Desarrollo de Aplicaciones Web


PRA: Miquel Antoni Capellà Arrom












## 1








Título del trabajo:
Freetter: Una plataforma newsletter open source, 100%
gratuita, Carbon tracking integrado y transparencia
radical.

Nombre de la autor:

## Cristian Andrés Vacacela Procel

Nombre del PRA:
## Miquel Antoni Capellà Arrom

Fecha de entrega:
## 2/11/2025

## Titulación:
Técnico Superior en Desarrollo de Aplicaciones Web

Idioma del trabajo:
## Castellano

Palabras clave:
Newsletter; Plataforma colaborativa; AGPLv3

Resumen del Trabajo:
Freetter, una plataforma web de newsletters de código
abierto, orientada a la sostenibilidad, la colaboración y la
transparencia económica. La memoria integra tres
dimensiones complementarias: una visión ética y social
crítica con el modelo de plataformas cerradas y
extractivas dominantes, un esqueleto estructural y
narrativo que organiza el proyecto como un MVP realista
desarrollado bajo recursos limitados, y un análisis técnico
exhaustivo que justifica cada decisión arquitectónica,
tecnológica y de despliegue. El resultado es un proyecto
que demuestra competencias avanzadas en arquitectura
web, DevOps, seguridad, optimización de rendimiento y
diseño centrado en la persona, sin renunciar a una fuerte
impronta de responsabilidad social y ambiental.




## 2




## Abstract
El presente documento formaliza la memoria final del Trabajo de Fin FP (TFG) para el
título de Técnico Superior en Desarrollo de Aplicaciones Web, cuyo núcleo es el diseño
y la implementación de Freetter, una plataforma web open source de newsletters
enfocada en la sostenibilidad, la colaboración y la transparencia económica y operativa.
Esta iniciativa se concibe como una respuesta crítica a las limitaciones estructurales
observadas en los servicios dominantes del mercado, tales como los modelos
económicos basados en comisiones elevadas, la gobernanza opaca sobre el uso de
datos y la ausencia de reflexión sobre el impacto ambiental derivado del envío masivo
de correos electrónicos.

Freetter propone una solución 100% código abierto, con una marcada vocación
comunitaria y sostenible, que facilita la creación, edición, publicación y gestión de
boletines de manera gratuita y funcional, sin aplicar comisiones sobre las donaciones
que los creadores puedan recibir directamente.

Arquitectónicamente, el proyecto se desarrolla como un Producto Mínimo Viable (MVP)
funcional sobre un monolito modular implementado en Laravel, utilizando Inertia.js y
React en el frontend. Esta elección estratégica evita la complejidad de mantener una
API REST separada, reduciendo la superficie de ataque y optimizando la eficiencia de
desarrollo. La capa de persistencia utiliza PostgreSQL, seleccionada por su robustez y
soporte de tipos avanzados como JSONB para el contenido estructurado del editor, y
Redis se emplea para la gestión eficiente de colas de trabajo y el caching, lo cual es
crucial para operar dentro de las restricciones de un servidor con 2GB de RAM. Como
pilar ético y ambiental, el proyecto integra mecanismos de Carbon tracking estimados
para visibilizar la huella de carbono asociada a la actividad editorial, mientras que la
licencia AGPLv3 garantiza que el código permanezca libre

## 3




## Índice

## Abstract 3
## Índice 4
Bloque 1: definición y contextualización 5
## 0.  Introducción 5

- Objetivos del proyecto 5

## 2. Justificación 6

## 2.1. Justificación Académica 6

## 2.2. Justificación Técnica 7

2.3. Justificación Social y ética 7
## 3. Metodología 8

- Análisis competencia 8

## Bloque 2: Definición Técnica 9
- Requisitos del Sistema 9
- Arquitectura del Sistema 10
- Information Architecture (IA) 10
- Modelo de Datos 11
## 9. Stack Tecnológico 12
Bloque 3: Producto y Experiencia 13
- Diseño de MVP 13
## 5.1 Fase 1: Core 13
## 5.2 Fase 2: Envío 13
## 5.3 Fase 3: Funciones Adicionales 13
5.4 Fase 4: Pulido y Sostenibilidad 14
- Funcionalidades Futuras (Proyecto Incremental) 14
- Estrategia de Diseño y UX 14
## 13. Core Web Vitals 15
Bloque 4: Ingeniería y Operaciones 16
- Seguridad y Protección de Datos (GDPR) 16
- Análisis de Complejidad por Módulo 17
- Despliegue e Infraestructura 17
- Estrategia DevOps 18
## Bloque 5: Cierre 19
## 18. Testing 19
- Riesgos y Limitaciones 20
## 20. Conclusiones 20
## 21. Anexos 21
21.1. Diagrama Conceptual de la Arquitectura del Sistema 21
21.2. Diagrama Entidad–Relación 21
21.3. Esquema de la Information Architecture 22
21.4. Cronograma de Sprints 22
## 22. Bibliografía 23


## 4




Bloque 1: definición y contextualización
## 0.  Introducción
En la última década, el formato de newsletter ha experimentado una revalorización
significativa, pasando de ser una herramienta complementaria a un canal de
comunicación digital fundamental. Este fenómeno, a menudo denominado la “vuelta al
email”, ha permitido a creadores, periodistas independientes y organizaciones construir
audiencias propias y estables, disminuyendo la dependencia de los algoritmos volátiles
y los cambios de política de las plataformas sociales. Sin embargo, el auge de las
newsletters ha consolidado plataformas especializadas (SaaS) que, si bien ofrecen
soluciones convenientes, imponen barreras significativas, como comisiones recurrentes
sobre los ingresos, funcionalidades restringidas a modelos premium, poca
transparencia en el uso de los datos y una ausencia total de reflexión sobre el impacto
ambiental inherente al envío masivo de correos electrónicos.

Freetter surge en este panorama como una iniciativa crítica y propositiva, buscando
subvertir el modelo de plataformas cerradas y extractivas. El proyecto aborda carencias
fundamentales identificadas en el ecosistema, como la falta de herramientas de
colaboración avanzada para pequeños equipos, la opacidad ambiental y la restricción
sobre la propiedad del código fuente y de los datos de los suscriptores. Frente a ello, se
plantea una plataforma basada en principios de código abierto, sostenibilidad y
gobernanza comunitaria, construyendo un MVP que sea viable de desplegar y
mantener con recursos económicos y computacionales muy limitados. Desde el punto
de vista formativo, el desarrollo de Freetter permite aplicar de forma integrada las
competencias esenciales del ciclo de Desarrollo de Aplicaciones Web, abordando el
diseño de arquitecturas web modernas, seguridad, DevOps y evaluación del
rendimiento y la escalabilidad bajo restricciones reales

- Objetivos del proyecto

El objetivo general del proyecto es desarrollar un MVP funcional de una plataforma de
newsletters open source, colaborativa y sostenible, que habilite a cualquier creador,
colectivo social u organización a gestionar y distribuir boletines de forma gratuita, con
donaciones directas opcionales y la inclusión de un cálculo estimado de la huella
ambiental de los envíos.

A nivel de funcionalidad específica, el proyecto se enfoca en la implementación de un
panel de control accesible que permita la gestión completa de las newsletters,
incluyendo su creación, edición y publicación. Esto se logra mediante la integración de
un editor moderno basado en bloques (Editor.js), diseñado para facilitar la
producción de contenido estructurado y fácilmente adaptable a formatos email-friendly.
Para soportar la creación de contenido colectiva, se implementa un sistema simple de

## 5



roles (Owner y Writer) para colaboración básica dentro de un workspace. La gestión de
suscriptores soporta tanto altas manuales como la robusta importación de ficheros
CSV, incluyendo la prevención de duplicados.

Un requisito funcional crítico es el envío de newsletters a través de colas de trabajo
asíncronas, lo cual resulta indispensable para evitar el bloqueo de la interfaz durante
el procesamiento de envíos masivos. Funcionalidades complementarias incluyen la
integración de un módulo de clips para almacenar y reutilizar fragmentos de contenido,
la capacidad de integrar donaciones directas mediante enlaces a proveedores
externos sin aplicar comisiones de la plataforma, un Explorer público básico para el
descubrimiento de contenido y, fundamentalmente, la incorporación de un cálculo
estimado de huella de carbono por envío, visible para el creador.

En el plano técnico y arquitectónico, los objetivos se centran en el diseño de una
arquitectura monolítica modular basada en Laravel, estructurada por dominios
funcionales. Se busca la integración fluida de React mediante Inertia.js,
consiguiendo una experiencia de SPA eficiente sin exponer una API REST separada.
La optimización de rendimiento es vital, dada la restricción de un servidor con 2GB de
RAM, lo que requiere un ajuste fino en la configuración de PHP-FPM, PostgreSQL y
Redis. Se establece la meta de aplicar buenas prácticas DevOps a través de GitHub
Actions para despliegues controlados y se busca garantizar una seguridad robusta
mediante la autenticación sin contraseñas con magic links, protección CSRF y
sanitización rigurosa del contenido HTML generado por el editor. Finalmente, se
documenta la Information Architecture y se define un backlog de evolución (V1, V2) que
garantiza la escalabilidad futura sin obligar a reescrituras profundas de la arquitectura
central


## 2. Justificación
## 2.1. Justificación Académica
Desde una perspectiva académica, Freetter ofrece un marco integral para la aplicación
y demostración práctica de las competencias clave del título de DAW. Se enfrenta el
desafío de diseñar una arquitectura web moderna, articulando la separación de
responsabilidades entre los dominios, los Actions de negocio, los controladores, las
colas de trabajo y los componentes de interfaz. El proyecto exige la utilización
avanzada de un framework backend maduro (Laravel) que integra ORM, middleware,
políticas de autorización y herramientas de testing. Se adopta un patrón de diseño
avanzado al utilizar Inertia.js para crear una SPA sin la sobrecarga de una API REST
separada, unificando la lógica de ruteo y datos para una experiencia fluida. La gestión
de la base de datos relacional (PostgreSQL) incluye el manejo de tipos JSONB para el
contenido estructurado y el diseño de índices para asegurar el rendimiento. La
implementación de prácticas de seguridad web, la mitigación de ataques comunes
(como N+1 queries) y la integración de prácticas DevOps básicas (CI/CD y
monitorización en un entorno de producción real) confieren al proyecto un carácter

## 6



transversal, ideal para evaluar la madurez técnica y la capacidad de tomar decisiones
técnicas bajo restricciones reales.
## 2.2. Justificación Técnica
La selección del stack tecnológico y la arquitectura de Freetter se basa en una
evaluación de alternativas que prioriza la eficiencia y la simplicidad operativa frente a
las condiciones del proyecto.

Se opta por una arquitectura de monolito modular en Laravel, combinada con Inertia.js
y React. Esta decisión permite la encapsulación lógica por dominios (Newsletter,
Subscriber, etc.) dentro de un único despliegue, evitando la sobrecarga de
coordinación, observabilidad distribuida y complejidad que supondría una arquitectura
de microservicios o una SPA totalmente desacoplada, algo injustificado para un TFG en
un único servidor.

La integración de Inertia.js es fundamental, ya que actúa como capa de enlace entre el
backend y el frontend, eliminando la necesidad de mantener un contrato de API REST
separado, lo cual simultáneamente reduce la superficie de ataque y los puntos de fallo.
PostgreSQL se selecciona por su robustez, su soporte para relaciones complejas y la
funcionalidad JSONB, la cual es óptima para almacenar el contenido de bloques que
genera el editor.
El uso de Redis como backend de colas y caché, aprovechando su velocidad e
integración nativa con Laravel, simplifica la infraestructura al evitar la introducción de
sistemas de colas adicionales como RabbitMQ.
La delegación del envío de emails a un proveedor externo, como Mailgun, garantiza
una entregabilidad profesional y la gestión de rate limiting y reintentos, lo cual es
esencial en esta fase inicial.

El stack elegido equilibra la madurez del ecosistema, la alineación con la formación de
DAW y la optimización necesaria para un servidor de 2GB.
2.3. Justificación Social y ética
Freetter se concibe explícitamente como una alternativa ética a las plataformas
comerciales, articulando su justificación social en varios ejes. La accesibilidad
económica se garantiza al ofrecer un modelo sin comisiones ni features bloqueadas, lo
que permite que colectivos con recursos limitados (proyectos comunitarios, ONG)
accedan a herramientas profesionales de comunicación sin coste obligatorio.
La libertad de código y gobernanza se asegura mediante la licencia AGPLv3, que
garantiza que el código de cualquier instancia SaaS derivada deba ser compartido,
previniendo la privatización futura del proyecto y fomentando una gobernanza
comunitaria transparente. En cuanto a la privacidad y protección de datos, el diseño se
alinea con el cumplimiento del GDPR desde el inicio, respetando el derecho al olvido,
evitando la monetización de datos de suscriptores y estableciendo políticas claras. El
factor de sostenibilidad ambiental se integra en el núcleo del producto a través del
cálculo estimado de carbon tracking por envío, que visibiliza la huella de carbono y abre
la puerta a futuras integraciones con iniciativas de compensación o hosting renovable.

## 7



Estas consideraciones éticas se traducen directamente en decisiones arquitectónicas
concretas, como el uso de un stack libre y la transparencia de las métricas ambientales.

## 3. Metodología
El desarrollo de Freetter se ha llevado a cabo siguiendo una metodología Agile
adaptada al contexto de un proyecto individual, enfocándose en un desarrollo
incremental y basado en sprints de duración acotada.
La estrategia fundamental fue priorizar la construcción de un Producto Mínimo Viable
(MVP) completo y estable. Este enfoque iterativo permite validar tempranamente las
decisiones arquitectónicas, detectar riesgos técnicos antes de que se vuelvan críticos y,
crucialmente, mantener el alcance controlado, evitando la ambición excesiva (scope
creep) que puede paralizar proyectos individuales.
La gestión del backlog funcional se estructura en niveles definidos (MVP, V1, V2), lo
cual permite distinguir claramente las funcionalidades que forman parte del TFG de
aquellas reservadas para la evolución futura, aportando una visión estratégica.
El control de versiones se sustenta en un flujo Git disciplinado, que utiliza las ramas
main para código estable, develop para integración y feature/* para el desarrollo
de funcionalidades específicas, asegurando la calidad mediante pull requests. Esta
organización facilita la automatización de la Integración Continua (CI) a través de
GitHub Actions, garantizando que los tests se ejecuten automáticamente en cada push.
La validación continua de la calidad se realiza mediante la definición de tests unitarios
para la lógica de negocio sensible (cálculo de huella, validaciones) y tests de
características (feature tests) para cubrir los flujos más críticos de usuario, como la
creación y el envío de newsletters y las restricciones de roles.

- Análisis competencia
El análisis de la competencia se establece mediante la comparación y posicionamiento
de Freetter frente a cuatro referentes clave: LetterBucket, Substack, Ghost y listmonk.
LetterBucket (SaaS comercial) destaca por su facilidad de uso y la experiencia pulida
en la migración de listas. Freetter asume de este competidor la importancia de un
onboarding claro y la necesidad de un importador CSV robusto con mensajes de
validación precisos. No obstante, el modelo propietario y comercial de LetterBucket
contrasta con la filosofía open source y sin comisiones de Freetter.
Substack (plataforma dominante propietaria) se ha consolidado por integrar
publicación y pagos recurrentes, aunque exige una comisión elevada. La principal
lección extraída es la de priorizar una experiencia de autor extremadamente simple y
fluida, reduciendo al máximo los pasos entre la redacción y el envío. Freetter se
posiciona como una alternativa ética, rechazando las comisiones y los modelos de
bloqueo de features que limitan a proyectos de bajo poder adquisitivo

## 8



## .
Ghost (CMS open source con newsletters) demuestra que es viable construir proyectos
OSS sostenibles y autoalojados. Freetter se inspira en su modelo para demostrar que
puede ser competitivo, y extrae la necesidad técnica de un pipeline de conversión
eficiente del contenido estructurado a HTML de email, apoyado en herramientas como
MJML. Se elige, sin embargo, un stack diferente (Laravel/PHP) alineado con la
formación del TFG.
Listmonk (gestor de mailing autoalojado) es un referente de escalabilidad para
grandes volúmenes de envíos. Listmonk subraya la complejidad de gestionar
directamente la entregabilidad y el rate limiting. En consecuencia, Freetter opta por
delegar el envío a un proveedor externo en el MVP (como Mailgun), concentrando los
recursos limitados en la arquitectura y la funcionalidad de producto, y reservando el
escalado infraestructural de envío propio para fases posteriores, siguiendo la hoja de
ruta observada en listmonk.
Freetter sintetiza las lecciones de usabilidad de Substack y la viabilidad del código
abierto de Ghost, adaptando la solución a un stack relevante y eficiente, y
estableciendo su posición como una plataforma ética, sin comisiones y enfocada en la
sostenibilidad digital.

## Bloque 2: Definición Técnica
- Requisitos del Sistema
La definición del sistema se articula a través de un conjunto de requisitos funcionales,
que especifican el comportamiento esperado de Freetter, y requisitos no funcionales,
que determinan las características de calidad, rendimiento y seguridad bajo las
restricciones del entorno.

A nivel funcional, el MVP debe proporcionar un CRUD completo para la gestión de
newsletters, incluyendo la edición mediante un editor moderno basado en bloques
(Editor.js), configurado con un conjunto acotado de tipos para mitigar la complejidad y
el riesgo de sobrecarga. Se exige un sistema de autenticación sin contraseñas a través
de magic links, por motivos de seguridad y para reducir las barreras de entrada al
usuario.
La gestión de suscriptores debe soportar tanto altas manuales como la importación de
ficheros CSV, con la complejidad de validar datos y detectar duplicados. El envío de
contenido debe realizarse indefectiblemente a través de colas de trabajo asíncronas
para asegurar la estabilidad del sistema durante el procesamiento masivo. Además, la
plataforma debe integrar el módulo de clips para contenido reutilizable, la configuración
de donaciones directas a creadores a través de enlaces externos, garantizando que
Freetter no aplique comisiones, y la visualización de un cálculo estimado de huella de
carbono por envío, sirviendo como mecanismo de sensibilización ambiental.


## 9



En el ámbito no funcional, la limitación impuesta por el hosting (un Droplet de 2GB de
RAM) introduce restricciones críticas:

● Se requiere que el sistema soporte un número razonable de decenas de
usuarios concurrentes, manteniendo un tiempo de respuesta p95 del backend
inferior a 500ms.
● La experiencia de usuario debe cumplir objetivos de Core Web Vitals, como un
LCP (Largest Contentful Paint) inferior a 2,5 segundos, y garantizar una baja
inestabilidad visual (CLS < 0,1) y una interactividad ágil (INP < 200ms).
● La arquitectura debe ser modular para permitir el crecimiento incremental sin
reescritura, e incorporar seguridad y protección de datos (GDPR compliance),
incluyendo mecanismos para la exportación y eliminación de datos personales,
así como una cobertura de tests mínima para la lógica crítica.

- Arquitectura del Sistema
La arquitectura de Freetter se define como un monolito modular implementado en
Laravel. Esta elección se considera sólida y apropiada para el contexto de un TFG con
recursos limitados, ya que evita la sobrecarga operacional, de coordinación y de
observabilidad asociada a arquitecturas distribuidas como microservicios.

El sistema se estructura internamente orientado a dominios funcionales, priorizando la
cohesión lógica sobre una separación estricta por capas técnicas. Se distinguen
dominios clave como Newsletter (lógica de envío y conversión de contenido),
Subscriber (gestión e importación de listas), Clip (contenido reutilizable), Donation
(configuración de pasarelas externas) y Workspace/Users (control de roles y permisos).

La interacción entre el backend y el frontend se resuelve mediante Inertia.js, actuando
como una capa de enlace. Esta solución permite disfrutar de una experiencia de
aplicación de página única (SPA) utilizando React en el frontend, sin la necesidad de
diseñar, mantener y exponer una API REST separada. Esto simplifica
significativamente la superficie de ataque y reduce los puntos de fallo, al tiempo que
unifica la lógica de ruteo y de datos.

A nivel transversal, la arquitectura depende críticamente de colchas de trabajos (Job
Queues) respaldadas por Redis, esenciales para delegar operaciones costosas como
el envío de correos o el procesamiento de la importación de archivos CSV, evitando así
el bloqueo de la interfaz HTTP. La seguridad se aplica mediante middleware que
asegura la validación de ownership en cada operación, garantizando que los usuarios
solo manipulen recursos dentro de su workspace.

- Information Architecture (IA)
La Information Architecture (IA) de Freetter organiza la experiencia de usuario y la
navegación, distinguiendo claramente entre la zona pública y la zona autenticada.


## 10



La Zona Pública está diseñada para el descubrimiento y el onboarding. Incluye la
página principal de landing, el endpoint de login por magic link (donde el usuario solicita
su enlace temporal), y el Explorer Público, que lista newsletters abiertas para el
descubrimiento de contenido mediante filtros básicos. La vista pública individual de
cada newsletter también es accesible mediante un identificador legible (slug).

La Zona Autenticada (Dashboard) centraliza las herramientas para los creadores.
Sus secciones principales se corresponden con los dominios funcionales:

● Newsletters: Permite el listado, creación, edición (con el editor de bloques) y la
previsualización antes del envío.
● Suscriptores: Incluye el listado, la posibilidad de alta manual y el flujo de
importación CSV.
● Clips: Dedicada al listado, creación y edición de fragmentos de contenido
reutilizable.
● Donaciones: Configuración del enlace externo para la recepción de fondos por
parte del workspace.
● Ajustes: Orientada a la gestión de equipo (roles Owner/Writer) y
configuraciones de perfil.

Los flujos de usuario más críticos, como la creación y envío, implican la redacción en el
editor, el guardado del borrador, la revisión en la previsualización (donde se estima la
huella de carbono) y, finalmente, el encolamiento asíncrono del envío masivo. El flujo
de importación de suscriptores requiere una validación previa del CSV cargado para
detectar errores de formato y duplicados antes de que el usuario confirme el
procesamiento final, que puede realizarse en segundo plano.

- Modelo de Datos
El Modelo de Datos se fundamenta en una base de datos PostgreSQL, seleccionada
por su robustez y el soporte avanzado de tipos, lo cual es crucial para la gestión
eficiente de la información. La arquitectura de datos combina el modelo relacional
clásico con la flexibilidad de los campos JSONB.

Las entidades centrales del sistema son:

● User: Representa al creador, con atributos básicos y la participación en uno o
varios Workspaces.
● Workspace: Es el contenedor lógico que agrupa todos los recursos del proyecto
(newsletters, suscriptores, clips). La relación entre User y Workspace se modela
mediante una tabla intermedia que incluye el atributo
role (Owner o Writer en el
MVP), permitiendo la colaboración multi-autor y la futura expansión de roles.
● Newsletter: Contiene metadatos como el título y el estado, pero su
característica distintiva es el almacenamiento del contenido en formato JSON de
Editor.js. El uso de JSONB en este campo es vital para permitir la evolución
flexible del esquema de contenido de bloques.

## 11



● Subscriber: Almacena los datos de los destinatarios, aplicando una restricción
de unicidad a nivel de Workspace y Email. Se incorpora soft deletes en esta
entidad para permitir la auditoría de bajas.
● Clip: Almacena fragmentos de contenido reutilizable, también en formato
## JSONB.
● Send: Registra cada acción de envío a un conjunto de suscriptores, esencial
para la auditoría y la posterior recopilación de métricas.
● DonationConfig: Registra los enlaces externos de donación asociados a un
## Workspace.

La integración de tipos JSONB optimiza el manejo de contenido semiestructurado del
editor, mientras que la base relacional soporta la compleja gestión de permisos y
pertenencia a Workspace.

## 9. Stack Tecnológico
La selección del stack para Freetter es una decisión arquitectónica estratégica que
equilibra la madurez tecnológica con la necesidad de eficiencia extrema para operar en
un servidor de 2GB de RAM.



## 12
Componente Tecnología Justificación y Función
Backend Laravel Ecosistema maduro y alineado con la
formación DAW, proporciona un sistema de
colas nativo (Queue) y un potente ORM
(Eloquent).
## Frontend React 18 +
## Inertial.js
Permite una experiencia de SPA moderna
sin requerir una API REST separada, lo que
reduce la complejidad y la superficie de
ataque. Inertia actúa como el puente entre el
routing de Laravel y los componentes de
## React.
Base de Datos PostgreSQL 15 Seleccionado por su robustez, excelente
manejo de transacciones, y esencialmente
por el soporte del tipo JSONB, que es ideal
para almacenar el contenido estructurado
generado por
Caché y Colas Redis 7 Utilizado tanto para el caching de sesiones
como para el backend de las colas de
trabajo. Su velocidad e integración nativa
con Laravel simplifican la infraestructura al
evitar la adición de gestores de colas más
pesados. La configuración de AOF
(Append-Only File) se requiere para
garantizar la persistencia.
Email Mailgun Se delega el envío a este proveedor externo




Esta arquitectura monolítica modular (Laravel + Inertia) es una elección inteligente que
optimiza los recursos de desarrollo y minimiza los trade-offs técnicos inherentes al
contexto académico y de recursos limitados. El patrón de diseño incluye la mitigación
activa de riesgos, como el uso de cursors y chunks de 100 elementos en envíos
masivos para prevenir el agotamiento de memoria.


Bloque 3: Producto y Experiencia
- Diseño de MVP
El diseño del Producto Mínimo Viable (MVP) para Freetter se sustenta en una
estrategia de desarrollo incremental Agile, priorizando la estabilidad y la demostración
de la viabilidad técnica y funcional de la plataforma. La hoja de ruta del MVP se
segmenta en cuatro fases consecutivas, garantizando que el producto resultante sea
coherente y defendible como un sistema completo.
## 5.1 Fase 1: Core
En esta fase se concentró en establecer los pilares estructurales del sistema, lo cual
incluyó la implementación completa del sistema de autenticación mediante magic link,
la definición exhaustiva del modelo de datos y sus relaciones en PostgreSQL, y la
integración básica del editor basado en bloques (Editor.js) para la creación de
newsletters en borrador.
## 5.2 Fase 2: Envío
Abordó el flujo funcional crítico, desarrollando la gestión de suscriptores, incluyendo la
importación simple a través de archivos CSV y el proceso de conversión del contenido
estructurado de JSON a un HTML seguro y compatible con clientes de correo (apoyado
en herramientas como MJML). El envío masivo se implementó rigurosamente a través
de colas de trabajo asíncronas.
## 5.3 Fase 3: Funciones Adicionales
Se incorporó los elementos de colaboración y contenido reutilizable, incluyendo el
sistema de clips, la implementación de roles básicos de Owner y Writer para la gestión

## 13
en el MVP para asegurar la alta
entregabilidad y la gestión de rate limiting y
reintentos, concentrando el esfuerzo interno
en la funcionalidad de producto.
Editor Editor.js Un editor basado en bloques que genera
contenido estructurado en JSON, facilitando
su posterior conversión a HTML compatible
con clientes de correo mediante
herramientas como MJML.



de permisos, y la vista de previsualización esencial para que el creador revise el email
antes de su lanzamiento.

5.4 Fase 4: Pulido y Sostenibilidad
Cerró el MVP con la integración del módulo de donaciones mediante enlace externo, el
Explorer público básico para el descubrimiento de contenido, y la implementación del
cálculo estático de huella de carbono por envío, cumpliendo así con los objetivos éticos
centrales del proyecto. Esta segmentación por fases aseguró el control del alcance
(scope creep) y permitió validar las decisiones arquitectónicas de manera temprana.

- Funcionalidades Futuras (Proyecto Incremental)
La arquitectura modular de Freetter fue diseñada específicamente para facilitar un
crecimiento funcional sin requerir reescrituras profundas, lo cual se traduce en un
backlog estructurado en versiones post-MVP (V1, V2)
## .
La Versión 1.1 (V1.1) se concibe como una mejora inmediata centrada en la robustez y
la experiencia del creador, incluyendo la programación de envíos para fechas futuras, la
adición de estadísticas básicas como tasas de apertura y clics, la creación de templates
adicionales basados en MJML, y la funcionalidad esencial de exportación de
suscriptores.

La Versión 1.2 (V1.2) se enfoca en la maduración de la plataforma hacia un servicio
semi-profesional, incorporando la gestión de webhooks de proveedores de email (como
Mailgun) para procesar eventos críticos como bounces y reclamaciones, la
segmentación de suscriptores basada en criterios o etiquetas, la posibilidad de acceder
a un historial de versiones de una newsletter, y la exposición de una API pública
limitada para integraciones sencillas.

A largo plazo, la Versión 2.0 (V2.0) persigue la visión comunitaria y avanzada de
Freetter, introduciendo la capacidad de múltiples workspaces por usuario,
herramientas de comentarios colaborativos en borradores, la integración con sistemas
externos a través de plataformas de automatización (como Zapier o n8n), y la creación
de un marketplace de plantillas, lo cual fomenta una economía colaborativa en torno al
diseño y la usabilidad. Este roadmap documentado asegura la escalabilidad estratégica
y demuestra la comprensión de los ciclos de vida del software más allá de la entrega
académica.

- Estrategia de Diseño y UX
La Estrategia de Diseño y Experiencia de Usuario (UX) constituye un pilar central de
Freetter, concebido para que la plataforma sea igualmente accesible para creadores
con experiencia técnica y para colectivos sin conocimientos avanzados. El diseño
busca simplicidad en el flujo principal, minimizando los pasos entre la autenticación y el

## 14



envío efectivo de una newsletter, siguiendo la inspiración de plataformas como
Substack, pero rechazando explícitamente su modelo económico
## .
Se ha priorizado la accesibilidad (WCAG 2.1 Nivel AA) desde el inicio, incluyendo un
contraste suficiente, navegación por teclado y el uso de etiquetas claras, buscando
reducir las barreras técnicas y cognitivas.

La autenticación se resuelve mediante el uso de autenticación sin contraseñas con
magic links, lo cual facilita el acceso a perfiles de usuario menos técnicos. A nivel de
interfaz, se aplica una estricta consistencia visual y jerarquía para asegurar la
legibilidad del contenido.

El diseño es completamente responsive, al tratarse de una plataforma de creación de
contenido escrito se busca derivar al público al uso de  tablet o desktop para la
redacción de borradores, aunque el diseño soporta la consulta de estadísticas y el uso
del Explorer tanto en escritorio como en tablet o móvil.

Finalmente, el editor de contenido se mantiene deliberadamente enfocado con un
conjunto limitado de bloques, evitando la sobrecarga de herramientas innecesarias y
manteniendo la experiencia centrada en la escritura.

Para poder visualizar al completo el trabajo de UX mediante el prototipado de
wireframes consultar el siguiente link: Wireframes

## 13. Core Web Vitals

La optimización del rendimiento y la calidad de la experiencia de usuario se evalúan
mediante las métricas Core Web Vitals (CWV), consideradas desde la fase de diseño
arquitectónico. [NOMBRE_PROYECTO] tiene objetivos claros en estas métricas para
garantizar una navegación fluida, a pesar de las limitaciones de un servidor de 2GB de
## RAM.

El objetivo para el LCP (Largest Contentful Paint), que mide el tiempo que tarda en
mostrarse el contenido principal, se establece en menos de 2.5 segundos en las
páginas clave del dashboard y en las vistas públicas. Para el CLS (Cumulative Layout
Shift), que evalúa la estabilidad visual, se trabaja para mantenerlo por debajo de 0.1,
asegurando que los componentes dinámicos tengan su espacio preasignado, evitando
saltos inesperados en la interfaz. Finalmente, el INP (Interaction to Next Paint), métrica
clave de interactividad, debe mantenerse por debajo de 200 milisegundos, mediante la
gestión eficiente del estado y la carga de componentes.

La arquitectura monolítica que combina Laravel y React a través de Inertia.js facilita el
cumplimiento de estos objetivos. Esto se logra mediante la carga diferida (lazy loading)
de componentes pesados, como el editor de bloques, que solo se inicializa en las vistas
de edición y no en todo el dashboard. Además, se aplica la división de código y la
agrupación lógica de dependencias (mediante Vite) para que el bundle principal sea
ligero. El principio de "El servidor es la fuente de verdad" (Server-Side First) reduce la

## 15




necesidad de gestión de estado compleja en el cliente, evitando re-renderizados
innecesarios y optimizando el INP. Este enfoque demuestra una aplicación práctica de
técnicas de ingeniería de rendimiento web.

Bloque 4: Ingeniería y Operaciones
- Seguridad y Protección de Datos (GDPR)
La seguridad se establece como un requisito de primera clase en
[NOMBRE_PROYECTO], integrando medidas proactivas que van desde la capa de
autenticación hasta la validación de la propiedad de los recursos. El proyecto adopta el
modelo de autenticación sin contraseñas mediante magic links. Esta decisión mitiga el
riesgo inherente al almacenamiento y gestión de contraseñas tradicionales, pero exige
una protección rigurosa de los enlaces generados, asegurando que sean de un solo
uso, estén cifrados y posean una caducidad limitada.

A nivel de protección de la aplicación web, se implementan controles esenciales para
mitigar riesgos comunes. Es indispensable la presencia de protección CSRF
(Cross-Site Request Forgery) en todas las operaciones que modifiquen el estado del
sistema.

Crucialmente, el contenido generado por el editor de bloques (Editor.js) debe ser
sanitizado rigurosamente en el backend utilizando mecanismos como DOMPurify, para
prevenir ataques de XSS (Cross-Site Scripting) al procesar y enviar el HTML resultante
a los clientes de correo.

La arquitectura de seguridad se refuerza mediante el control estricto de acceso y
pertenencia, donde la validación de ownership debe realizarse en cada operación. Este
middleware asegura que el usuario autenticado posee los permisos adecuados y
pertenece al workspace asociado al recurso que intenta manipular. Adicionalmente, se
recomienda la implementación de rate limiting en rutas sensibles, como el login y el
proceso de envío de newsletters, para prevenir ataques de fuerza bruta o abuso del
sistema.

Respecto al cumplimiento del GDPR (Reglamento General de Protección de Datos), el
diseño de [NOMBRE_PROYECTO] se alinea con la normativa desde el inicio. Esto se
traduce en la obligación de ofrecer al usuario la capacidad de exportar sus datos
personales y la implementación de mecanismos robustos para la eliminación de
cuentas y sus datos asociados. Para fines de auditoría, se utiliza la estrategia de soft
deletes en entidades críticas como las newsletters y los suscriptores.

Finalmente, se requiere la configuración de un sistema de backup diario de la base de
datos (por ejemplo,
pg_dump) con una retención definida, como siete días, para
garantizar la capacidad de recuperación ante fallos catastróficos.



## 16




- Análisis de Complejidad por Módulo
El desarrollo del MVP se realizó bajo la premisa de simplificar módulos complejos para
asegurar la viabilidad dentro de un proyecto individual con recursos limitados. Se
identificaron tres áreas principales de complejidad que requerían mitigación activa:

● Editor de Contenido (Editor.js): La implementación de un editor completo
conlleva un alto riesgo de sobrecarga y complejidad debido a la proliferación
de plugins y casos límite. La recomendación fue limitar la funcionalidad del
editor a un conjunto acotado de bloques esenciales (párrafo, encabezado, lista,
imagen y cita). Esta simplificación cubre las necesidades básicas de redacción al
tiempo que reduce la superficie de ataque y la carga de mantenimiento del
frontend.

● Envío Masivo de Emails: Esta es una funcionalidad de alto impacto y alta
probabilidad de fallo si no se gestiona correctamente. Un envío sin control puede
agotar la memoria del servidor de 2GB o exceder los límites (rate limiting) del
proveedor de email. La mitigación técnica es crítica:
○ Memoria: Se requiere el uso de cursor() y procesamiento en chunks
(lotes), idealmente de 100 elementos, al iterar sobre la lista de
suscriptores para prevenir el agotamiento de la memoria.
○ Rate Limiting: El riesgo de exceder el límite del proveedor (como
Mailgun) se mitiga implementando colas de trabajo con throttling,
limitando la tasa de procesamiento de envío a un valor seguro, como 60
correos por minuto.

● Importación de Suscriptores por CSV: La carga de archivos grandes puede
provocar un timeout en la petición HTTP, catalogado como un riesgo alto. La
mitigación consiste en procesar la importación como un job asíncrono en la cola.
Además, el MVP debe imponer un límite inicial de filas, por ejemplo 1000, con
validación síncrona para evitar problemas de latencia, reservando el
procesamiento en segundo plano para volúmenes confirmados como grandes.

Finalmente, el módulo de cálculo de huella ambiental se gestiona bajo el principio de
simplificación. Un cálculo preciso sería logísticamente insostenible y requeriría datos no
disponibles (geolocalización, mezcla energética del hosting). Por lo tanto, el MVP utiliza
una fórmula estimada simplificada basada en variables controlables, como el tamaño
del email y el número de suscriptores, cumpliendo el objetivo de sensibilización del
creador sin aspirar a una auditoría científica exhaustiva.

- Despliegue e Infraestructura
La infraestructura de producción de [NOMBRE_PROYECTO] se implementa sobre un
Droplet de DigitalOcean con mínimo 2GB de RAM, una elección que obliga a un diseño
y una configuración de alta eficiencia. Esta restricción es una justificación principal de


## 17




la elección del monolito modular sobre arquitecturas distribuidas, ya que maximiza el
uso de los recursos disponibles.

La configuración del stack se optimiza rigurosamente para garantizar la estabilidad y el
rendimiento, cumpliendo con el objetivo de soportar entre 50 y 80 usuarios
concurrentes bajo navegación normal y mantener un tiempo de respuesta p95 del
backend inferior a 500ms. El uso de OPcache activo es vital para incrementar la
capacidad del servidor, permitiendo potencialmente hasta 120 usuarios concurrentes y
40-50 requests por segundo.

Las decisiones de infraestructura clave incluyen:

● PHP-FPM: Debe configurarse con un número limitado de procesos hijo para
asegurar que el consumo total de memoria se mantenga dentro de los 2GB
disponibles, evitando el swapping o el crash del Droplet.
● PostgreSQL: Se ajustan los parámetros para balancear el uso de la memoria
entre el sistema operativo y la caché de la base de datos, además de utilizar
índices en queries frecuentes para evitar degradación de la UX.
● Redis: Se utiliza como backend de colas y caché. Para garantizar la persistencia
de los jobs en caso de fallo del servidor, es obligatorio configurar AOF
(Append-Only File). Un servicio supervisor debe encargarse de gestionar y
reiniciar los procesos de los queue workers para garantizar la continuidad del
envío asíncrono.

En el MVP, el servicio de envío de correos se delega a un proveedor externo (Mailgun),
aprovechando su free tier y sus capacidades profesionales de entrega y rate limiting.
Esta delegación permite concentrar los limitados recursos internos del servidor en la
lógica de negocio principal y el rendimiento de la interfaz.

- Estrategia DevOps
La Estrategia DevOps de [NOMBRE_PROYECTO] se basa en la automatización de la
integración y el despliegue para garantizar la calidad y la reproducibilidad. Se utiliza un
flujo Git basado en feature branching, donde las ramas se integran en
develop y
posteriormente en
main. El motor de la automatización es GitHub Actions, utilizado
para la Integración Continua (CI) y el Despliegue Continuo (CD).

En el pipeline de CI, se ejecutan tests automatizados y tareas de linting en cada push y
pull request, asegurando que solo el código que cumple con los estándares mínimos de
calidad pueda fusionarse.

El CD permite que el código probado se despliegue a producción de manera
reproducible. Para optimizar el rendimiento en producción, se utilizan comandos de
cacheo de la configuración y de las rutas de Laravel.

A nivel de observabilidad, el proyecto incorpora herramientas de monitorización básica
para detectar fallos rápidamente:

## 18




● UptimeRobot: Utilizado para el monitoreo gratuito del tiempo de actividad
(uptime) del Droplet cada cinco minutos.
● Sentry: Se integra para el seguimiento de errores en producción, capturando
excepciones hasta el límite gratuito de 5.000 eventos.
● Laravel Telescope: Se utiliza como herramienta de debugging y monitorización
solo en el entorno de desarrollo.

Esta estrategia demuestra una comprensión de las prácticas profesionales de DevOps
y la aplicación de automatización en un contexto de desarrollo individual.

## Bloque 5: Cierre
## 18. Testing
La estrategia de testing implementada en [NOMBRE_PROYECTO] se diseñó no solo
para validar la funcionalidad, sino para garantizar la calidad en los flujos de negocio
más críticos, asumiendo el principio de que la calidad debe ser un eje transversal del
proyecto. El enfoque priorizado se divide en tres niveles que buscan asegurar una
cobertura mínima del >40% en los módulos sensibles y demostrar una estrategia de
pruebas consciente y focalizada.

En primer lugar, los tests de características (feature tests) se establecen como prioridad
principal. Estos tests cubren los flujos de usuario completos, verificando que un usuario
con el rol adecuado (Owner o Writer) puede, por ejemplo, crear, editar y enviar
newsletters. Es crucial que estos tests aseguren la validación de la autorización por
roles, confirmando que los usuarios con permisos restringidos no puedan ejecutar
acciones que excedan su alcance, como la eliminación de una newsletter sin la
autorización de Owner. También se comprueba que el acto de envío genera los
trabajos esperados en las colas asíncronas y que el registro de envíos (Send) se
realiza correctamente.

En segundo lugar, los tests unitarios se concentran en la lógica de negocio sensible,
asegurando la solidez de los componentes aislados. Esto incluye la validación de la
correcta conversión del contenido estructurado JSON de Editor.js a HTML seguro y
compatible con clientes de correo, lo cual es vital para prevenir XSS. También se
requiere testear la consistencia del cálculo estimado de la huella de carbono para
diferentes entradas.

Finalmente, los tests de integración se enfocan en simular la interacción con servicios
externos, como el proveedor de envío de emails (Mailgun). Estos se realizan a menudo
utilizando dobles de prueba para verificar que la plataforma construye correctamente la
petición saliente y que procesa de manera adecuada las respuestas y los códigos de
estado del servicio externo. La cobertura se prioriza en módulos de alto impacto, como
el envío de correos, el control de acceso y la sanitización del contenido, que son los
que podrían tener un fallo más visible o peligroso.


## 19




- Riesgos y Limitaciones
La viabilidad del MVP de [NOMBRE_PROYECTO] se basa en una gestión proactiva de
los riesgos inherentes al contexto de desarrollo (un Droplet de 2GB de RAM y un TFG
individual). Reconocer estos límites refuerza la credibilidad del proyecto y la
comprensión realista del contexto operativo.

Se identificaron riesgos técnicos críticos en el flujo de envío masivo:

● Riesgo de memoria agotada en envíos masivos es alto y su impacto es crítico.
Este se mitiga mediante la implementación rigurosa de
cursor() y el
procesamiento por lotes (chunks) de 100 elementos al iterar sobre la lista de
suscriptores, evitando la carga completa de datos en la memoria del servidor.
● Otro riesgo técnico relevante es el rate limiting del proveedor de email,
mitigado mediante el uso de colas de trabajo con throttling configurado a un
límite seguro, como 60 envíos por minuto.
Para la importación de CSV, el riesgo de timeout se gestiona procesando la
carga del fichero como un job asíncrono.
● Si Redis cae, las colas no funcionan, para lo cual se requiere una configuración
de AOF (Append-Only File) para la persistencia y un plan de contingencia para
un fallback a un sync driver.
● A nivel de seguridad, existen riesgos en la gestión del magic link si no se
controlan estrictamente su expiración, cifrado y estado de un solo uso.
● Es crucial también la mitigación del riesgo de XSS asociado al contenido rico
generado por el editor, mediante una sanitización estricta en el backend

Las limitaciones de alcance son un trade-off consciente. El proyecto no aspira a replicar
la totalidad de las funcionalidades de plataformas comerciale:
● La funcionalidad de cálculo de huella de carbono se implementa como una
estimación simplificada basada en una fórmula estática (ej.,
tamaño_email_kb ×
num_suscriptores × 0.0002 kg CO2
), lo cual es suficiente para el objetivo de
sensibilización del creador, pero no para una auditoría científica exhaustiva.
● Funcionalidades avanzadas, como los webhooks de Mailgun (para bounces y
reclamaciones) o la segmentación de suscriptores, se han diferido
estratégicamente a versiones posteriores (V1.2) para asegurar la entrega del
MVP principal.
- IA Anexo
20.1. Plano de la Estrategia

El Plano de la Estrategia define por qué estamos construyendo Freetter, identificando los
Objetivos del Producto (nuestros intereses) y las Necesidades del Usuario (los intereses de
nuestros usuarios).

## 20




A. Objetivos del Producto.  Los objetivos de Freetter se definen explícitamente como una
respuesta crítica y propositiva a los modelos de plataformas cerradas y extractivas,
centrándose en la sostenibilidad, la colaboración y la transparencia económica y operativa.

## Categoría
## Estratégica
Objetivo Específico de Freetter Requisito Estratégico Clave
Libertad y
## Gobernanza
Subvertir el modelo de plataformas
extractivas y cerradas
Garantizar la libertad del
código mediante la licencia
AGPLv3, previniendo la
privatización futura del
proyecto
## Sostenibilidad
## Ambiental
Abordar la ausencia de reflexión
sobre el impacto ambiental del
envío masivo de correos
electrónicos
Integrar mecanismos de
Carbon tracking estimados
para visibilizar la huella de
carbono asociada a la
actividad editorial
## Viabilidad
## Económica
(No-Extractiva)
Proporcionar una herramienta
profesional 100% gratuita a
colectivos con recursos limitados
Ser una plataforma sin
comisiones sobre las
donaciones directas que los
creadores puedan recibir
## Eficiencia
## Operacional
Demostrar la viabilidad de
desplegar un MVP sobre recursos
económicos y computacionales
muy limitados (servidor de 2GB de
## RAM)
Diseñar una arquitectura
monolítica modular optimizada
para operar bajo restricciones
de 2GB de RAM
Transparencia y
## Ética
Mejorar la confianza y la ética
digital, contrastando con la
opacidad de competidores
Transparencia radical y
cumplimiento del GDPR (ej.
exportación/eliminación de
datos)


## 21



B. Necesidades del Usuario. Las necesidades se segmentan en los dos tipos de usuarios
principales que interactuarán con la plataforma: los Creadores (quienes publican el contenido) y
los Lectores (quienes lo consumen y suscriben).
Creadores (Owner y Writer)
La necesidad principal de los creadores es gestionar y distribuir boletines de forma gratuita y
funcional, manteniendo la propiedad y el control.
● Necesidad de Autonomía y Propiedad: Necesitan el control total sobre los datos de sus
suscriptores y la garantía de que el código base está abierto.
● Necesidad de Sencillez en la Creación: Necesitan una experiencia de autor
extremadamente simple y fluida, con un editor moderno basado en bloques (Editor.js)
que facilite la producción de contenido estructurado y email-friendly.
● Necesidad de Colaboración: Requerimiento de un sistema simple de roles (Owner y
Writer) que permita la creación de contenido colectiva dentro de un workspace.
● Necesidad de Sostenibilidad Financiera: Necesitan la capacidad de recibir donaciones
directas sin que la plataforma extraiga una comisión.
● Necesidad de Conciencia Ambiental: Necesitan visibilidad sobre el impacto de sus
envíos a través del cálculo estimado de la huella de carbono.
● Necesidad de Eficiencia Operativa: Necesitan un flujo de envío robusto que no bloquee
la interfaz (a través de colas de trabajo asíncronas).
Lectores (Suscriptores y Audiencia Pública)
● Necesidad de Privacidad: Necesitan la certeza de que sus datos personales no se
monetizan y que el diseño cumple con los requisitos de GDPR (ej. derecho al olvido).
● Necesidad de Descubrimiento: Necesitan un mecanismo para encontrar newsletters
nuevas e interesantes (Explorer público).
● Necesidad de Acceso Sencillo: Necesitan un proceso de autenticación que reduzca las
barreras de entrada (autenticación sin contraseñas mediante magic links).

20.2. Plano del Alcance
El Plano del Alcance traduce los objetivos y necesidades estratégicas definidas anteriormente
en requisitos específicos para lo que el producto ofrecerá. Esto se articula a través de las
Especificaciones Funcionales (lo que el sistema debe hacer) y los Requisitos de Contenido (la
información y datos que debe gestionar).
A. Especificaciones Funcionales (Functional Specifications)
Las especificaciones funcionales definen las features del software product que Freetter debe
tener para cumplir su Estrategia.


## 22




## Dominio
## Funcional
## Especificación Funcional Clave
## (MVP)
## Justificación Estratégica
Gestión de
## Newsletters
Implementar CRUD completo para
newsletters, utilizando un editor
moderno basado en bloques
(Editor.js)
Satisfacer la necesidad del
Creador de sencillez en la
creación y contenido estructurado
Envío Asíncrono El envío de contenido debe
realizarse indefectiblemente a
través de colas de trabajo
asíncronas
Satisfacer la necesidad del
Creador de eficiencia operativa,
evitando el bloqueo de la interfaz
durante el procesamiento masivo
Suscripciones Soporte para altas manuales y
robustas importaciones de ficheros
CSV, incluyendo validación de
datos y prevención de duplicados
Satisfacer la necesidad del
Creador de funcionalidad
profesional, minimizando los
impedimentos (como los fallos en
la importación)
Sostenibilidad Visualización de un cálculo
estimado de huella de carbono por
envío en la previsualización
Cumplir con el Objetivo de
Sostenibilidad Ambiental del
producto, sirviendo como
mecanismo de sensibilización
Monetización Configuración de donaciones
directas mediante enlaces
externos, garantizando que
Freetter no aplique comisiones
Cumplir el Objetivo Económico
No-Extractivo
Seguridad y
## Acceso
Sistema de autenticación sin
contraseñas a través de magic
links
Satisfacer la necesidad de
acceso sencillo del usuario y
mitigar el riesgo de
almacenamiento de contraseñas
Colaboración Implementación de un sistema
simple de roles (Owner y Writer)
dentro de un workspace
Satisfacer la necesidad del
Creador de colaboración básica

## 23



Reutilización de
## Contenido
Integrar el módulo de clips para
almacenar y reutilizar fragmentos
de contenido
Mejorar la eficiencia en la
creación de contenido para el
## Creador
B. Requisitos de Contenido (Content Requirements)
Los requisitos de contenido definen los tipos de información que Freetter debe ofrecer y
gestionar para sus usuarios y para el público.


Tipo de
## Contenido
Requisito Específico de Freetter
## (MVP)
## Justificación Estratégica
## Contenido
## Editorial
El contenido de la newsletter debe
almacenarse como datos
semi-estructurados en formato
JSONB (generado por Editor.js)
Garantizar la flexibilidad del
esquema de contenido y su
futura evolución
## Contenido
## Público
Existencia de un Explorer público
básico que liste newsletters abiertas,
accesible mediante un identificador
legible (slug)
Satisfacer la necesidad del
Lector de descubrimiento
Contenido de
## Interfaz Crítico
Sanitización rigurosa del contenido
HTML resultante del editor en el
backend
Prevenir ataques de XSS
(Cross-Site Scripting),
cumpliendo el requisito de
seguridad
Contenido de
## Feedback
Inclusión de mensajes de validación
precisos en la importación de CSV.
Mensajes de error claros y
específicos (evitando placeholders
técnicos)
Asegurar la usabilidad del
sistema, especialmente en
flujos críticos, evitando la
ambigüedad
Contenido de
## Transparencia
Documentación de la Information
Architecture (IA) y un backlog de
evolución (V1, V2). Información sobre
la estimación de la huella de carbono
Cumplir con el Objetivo de
Transparencia Operativa y la
gestión de requerimientos
(scope creep)


## 24




## Metáfora Conceptual:
La relación entre el Plano de la Estrategia y el Plano del Alcance es como trazar el plano
arquitectónico de un edificio modular ético: La Estrategia decide que el edificio debe ser de
código abierto, energéticamente eficiente y accesible para todos (los grandes principios y
objetivos). El Alcance traduce esos principios en planos detallados, decidiendo exactamente
qué módulos se construirán en la primera fase (MVP), por ejemplo, que el sistema de
calefacción será un "carbon tracking estimado" y que todas las puertas serán magic links
(requisitos funcionales y de contenido específicos). Sin una Estrategia clara, el Alcance se
descontrolaría (el temido scope creep); sin un Alcance definido, la Estrategia sería solo una
buena intención.

20.1. Plano de Estructura
Según Jesse James Garrett, se enfoca en definir cómo las características y el contenido del
proyecto se interrelacionan. Esto se logra mediante el Diseño de Interacción (IxD), que
determina cómo el sistema se comporta, y la Arquitectura de Información (IA), que establece la
organización y navegación del contenido.

- Diseño de Interacción (IxD)
El Diseño de Interacción para Freetter debe reflejar la necesidad estratégica de una experiencia
de autor extremadamente simple y fluida, mientras gestiona la complejidad de las tareas de alto
impacto (como el envío masivo y la importación de datos) mediante el uso de colas de trabajo
asíncronas.
Flujo A: Creación y Envío de una Newsletter
Este flujo (cubierto por los feature tests) valida la capacidad del creador para llevar contenido
del borrador al envío masivo, integrando el componente ético de sostenibilidad.

Paso Happy Path (Comportamiento
Ideal del Sistema)
Caso de Error (Error
## Handling)
- Creación/Edición El usuario accede a la pantalla de
edición (CRUD completo). El
sistema carga Editor.js (basado en
bloques que genera JSON) y
componentes de React a través de
Inertia.js para una experiencia
SPA. El usuario puede insertar
fragmentos de contenido
reutilizable (clips).
Si el contenido incluye código
potencialmente malicioso (por
ejemplo, JS), la lógica de
negocio debe realizar la
sanitización rigurosa en el
backend antes de guardar,
mitigando el riesgo de XSS
(Cross-Site Scripting).

## 25



## 2. Previsualización
y Sostenibilidad
El creador revisa la versión final
del email. La interfaz muestra un
cálculo estimado de huella de
carbono por envío, sirviendo como
mecanismo de sensibilización
ambiental.
Si el creador no tiene los
permisos adecuados (por
ejemplo, rol Writer sin
autorización de envío, aunque
el MVP solo contempla
Owner/Writer con
funcionalidad básica), el
middleware de seguridad
debe prevenir el acceso a la
acción de envío.
- Confirmación de
## Envío
El creador pulsa el botón de
"Enviar". El sistema verifica el
estado del newsletter y la lista de
suscriptores asociados al
## Workspace.
Si el servidor (el Droplet de
2GB de RAM) está bajo alta
carga, el sistema debe
responder rápidamente, pero
el rate limiting debe aplicarse
a rutas sensibles, incluyendo
el proceso de envío.
- Feedback de
Cola de Espera
El sistema dispara inmediatamente
un Job asíncrono a la cola
respaldada por Redis, que se
encarga del procesamiento masivo
y la delegación a Mailgun. El
usuario recibe un mensaje de éxito
inmediato confirmando que el
envío ha sido encolado, evitando el
bloqueo de la interfaz HTTP.
Riesgo de agotamiento de
memoria: el Worker de la cola
mitiga este riesgo utilizando
cursor() y procesamiento en
chunks (lotes), idealmente de
100 elementos, al iterar sobre
la lista de suscriptores.
Flujo B: Importación de Suscriptores
Este flujo (catalogado como crítico) gestiona la entrada masiva de datos y requiere una
mitigación activa de riesgos como el timeout.

Paso Happy Path (Comportamiento
Ideal del Sistema)
Caso de Error (Error Handling)

## 26



- Carga de CSV El usuario navega a la sección
Suscriptores y carga un fichero
## CSV.
Riesgo de timeout: la carga de
archivos grandes se gestiona
procesando la importación como
un Job asíncrono en la cola. El
MVP puede imponer un límite
inicial de filas (por ejemplo,
1000) con validación síncrona.
- Validación de
## Errores
El sistema realiza una validación
previa síncrona del CSV cargado
para detectar errores de formato y
duplicados.
El sistema proporciona
mensajes de error y validación
claros y específicos (por
ejemplo, "El email X en la fila Y
es un duplicado"), en lugar de
mensajes técnicos como "Null
input field exception".
- Confirmación y
## Procesamiento
El usuario confirma el
procesamiento. El sistema inicia el
proceso asíncrono, delegando la
carga y la inserción de datos en la
cola.
Si Redis falla, las colas se
detienen. El sistema requiere la
configuración AOF
(Append-Only File) para la
persistencia de los jobs y un
plan de contingencia para evitar
la pérdida de trabajos.
## 4. Feedback
## Asíncrono
El usuario puede salir de la
pantalla y continuar usando la
aplicación, ya que el proceso se
realiza en segundo plano. La
interfaz proporciona un status
sobre el estado de la cola.
## —
20.1. Plano de Estructura
La Arquitectura de Información de Freetter organiza la plataforma en función de la funcionalidad
necesaria para cumplir con los objetivos estratégicos de la Zona Pública (descubrimiento) y la
Zona Privada (creación y gestión).
Esquema de Organización
Freetter utiliza principalmente un Esquema de Organización Jerárquico (o de árbol),
complementado por una organización por dominios funcionales dentro del Dashboard.
● Jerárquico: La estructura general sigue un modelo de árbol con dos ramas principales:
la Zona Pública y la Zona Autenticada (Dashboard).


## 27



● Funcional: Dentro de la Zona Privada, las secciones de primer nivel (Newsletters,
Suscriptores, Clips, Ajustes) corresponden directamente a los dominios funcionales
clave del software (Newsletter, Subscriber, Clip, Workspace/Users, DonationConfig).
Inventario de Navegación (Sitemap Teórico)
La IA distingue claramente entre las pantallas destinadas a la audiencia general y las
herramientas para los creadores.
Zona Pública (Discovery y Onboarding)
Esta zona está diseñada para el descubrimiento de contenido y la captación de nuevos
creadores, utilizando identificadores legibles (slug).
## ● Raíz:
## /
(Landing Page / Página principal)
## ● Acceso:
## /login
(Endpoint de solicitud de Magic Link)
## ● Descubrimiento:
## /explorer
(Explorer Público, listado de newsletters abiertas con
filtros básicos)
## ● Contenido:
## /n/{slug}
(Vista Pública Individual de una Newsletter)
Zona Privada (Dashboard)
El Dashboard centraliza las herramientas para los creadores.
## Resumen:
## /dashboard
(Panel de control accesible)
## Newsletters:
## ◦
## /newsletters
(Listado de newsletters - borrador y enviados)
## ◦
## /newsletters/create
(Creación, Editor de Bloques)
## ◦
## /newsletters/{id}/edit
(Edición, Editor de Bloques)
## ◦
## /newsletters/{id}/preview
(Previsualización, incluyendo Carbon Tracking)
## Suscriptores:
## ◦
## /subscribers
(Listado de suscriptores)
## ◦
## /subscribers/import
(Flujo de Importación CSV)
## ◦
## /subscribers/add
(Alta manual)
## Clips:
## ◦
## /clips
(Listado de fragmentos reutilizables)
## ◦
## /clips/create
(Creación/Edición)
Ajustes del Workspace:
## ◦
## /settings
(Ajustes de Workspace y perfil, Gestión de equipo Owner/Writer)


## 28



## Donaciones:
## ◦
## /donations
(Configuración del enlace externo para la recepción de fondos)
Justificación de la Agrupación
La IA del Dashboard se organiza mediante un patrón de diseño orientado a dominios
funcionales. La separación de secciones responde a esta encapsulación lógica de
responsabilidades.
¿Por qué "Donaciones" está separado de "Ajustes"?
La separación de Donaciones y Ajustes se justifica por las diferentes responsabilidades
funcionales y éticas que representan en la Estrategia de Freetter:
- Ajustes (/settings): Está orientada a la gobernanza interna del Workspace. Incluye la
gestión de equipo (roles Owner/Writer) y las configuraciones de perfil. Estas son
funcionalidades administrativas relacionadas con la identidad y el control de acceso del
sistema.
- Donaciones (/donations): Corresponde al dominio
DonationConfig
y está separada
para destacar la configuración del modelo económico no-extractivo. El objetivo estratégico
de Freetter es la transparencia y la provisión de herramientas gratuitas, permitiendo donaciones
directas a través de enlaces externos. Al mantener esta configuración en un módulo separado,
se subraya su carácter de funcionalidad específica relacionada con la sostenibilidad financiera
del creador, distinta de las configuraciones operativas del sistema.

## 21. Conclusiones
[NOMBRE_PROYECTO] se erige como un ejercicio de ingeniería de software
responsable y una demostración integral de las competencias adquiridas en el ciclo de
Desarrollo de Aplicaciones Web. El proyecto logra combinar una arquitectura web
moderna y realista con una reflexión crítica sobre el modelo económico, social y
ambiental de las plataformas tecnológicas dominantes.

El MVP demuestra la viabilidad de una alternativa ética y libre, rechazando
explícitamente los modelos de comisiones elevadas y features bloqueadas de
competidores como Substack y Mailchimp. Al estar bajo la licencia AGPLv3, el proyecto
garantiza que el código de cualquier servicio SaaS derivado permanezca abierto,
fomentando la gobernanza comunitaria y previniendo la privatización futura. El factor de
la sostenibilidad digital se integra en el núcleo del producto a través del Carbon
tracking, cumpliendo el objetivo de visibilizar la huella de carbono asociada a la
actividad editorial.

Técnicamente, el proyecto ha abordado desafíos complejos bajo restricciones severas.
Se diseñó un monolito modular basado en Laravel que integra React mediante
Inertia.js, una elección que se ha demostrado sólida y apropiada para un TFG con
recursos limitados, al eliminar la complejidad de una API REST separada y reducir la
superficie de ataque. Se implementaron colas asíncronas con Redis y PostgreSQL con
soporte JSONB, resolviendo la necesidad de escalabilidad y flexibilidad del contenido

## 29



estructurado. Además de la funcionalidad aparente, el TFG atiende rigurosamente a
requisitos no funcionales, incluyendo la seguridad (magic links, GDPR compliance), la
optimización de rendimiento para el servidor de 2GB y la aplicación de prácticas
DevOps (CI/CD con GitHub Actions).

El trabajo sienta las bases de un proyecto con potencial de crecimiento, ya que el
backlog documentado (V1.1, V1.2, V2.0) permite un crecimiento funcional y técnico
sostenible sin requerir reescrituras profundas. En síntesis, el TFG consolida
competencias clave de desarrollo web moderno y demuestra la capacidad del para
tomar decisiones técnicas fundamentadas bajo el peso de consideraciones éticas y
restricciones reales, preparando al futuro profesional para las exigencias del sector.









## 30




## 22. Bibliografía
## 22.1 Tecnologías Core

Laravel Documentation Contributors. (2025). Queues - Laravel 11.x Documentation [Sitio web].

Inertia.js Team. (2025). Inertia.js - Documentation [Sitio web].

React Core Team. (2025). React - Hooks & Performance [Sitio web].

The PostgreSQL Global Development Group. (2025). PostgreSQL Documentation - JSON
Types & Functions [Sitio web].

Redis Ltd. / Redis Open Source. (2025). Redis Documentation - Caching & Guides [Sitio web].

Editor.js Contributors. (2024). Editor.js - API & Tools (Documentación oficial) [Sitio web].

MJML Core Team. (2023). MJML - The Responsive Email Framework (Documentación / API)
[Sitio web].
## 22.2. Sostenibilidad Digital
The Green Web Foundation. (2025). Green Web Foundation — Home / Recursos sobre hosting
sostenible [Sitio web].

The Green Web Foundation — CO2.js Project. (2024). CO2.js — GitHub repository / Biblioteca
para estimar emisiones digitales [Repositorio GitHub].

Harding, X. / Mozilla Foundation. (2023). The Internet’s Invisible Carbon Footprint [Blog /
artículo].
## 22.3.. Recursos Multimedia
Theo - t3.gg. (2025, 14 enero). If you write HTML, watch this [Video]. YouTube.

Theo - t3.gg. (2025, 6 enero). Inertia 2.0: It’s like Next but better (and you can use React!)
[Video]. YouTube.

Qiu, J. (2025, 8 agosto). System Design Essentials: Rendering Strategies (SSR/ ...) [Video].
YouTube.
22.4. UX y accesibilidad
Garrett, J. J. (2010). The Elements of User Experience: User-Centered Design for the Web and
Beyond [Libro]. New Riders. (Referencia clásica para IA y UX).

W3C Web Accessibility Initiative (WAI). (2018). Web Content Accessibility Guidelines (WCAG)
2.1 [Normativa / Sitio web].



## 31
