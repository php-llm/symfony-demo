# LLM Chain - Symfony Demo Chatbot Application

Simple Symfony demo application on top of [LLM Chain](https://github.com/php-llm/llm-chain).

> [!CAUTION]
> This is currently work in progress.

## Requirements

What you need to run this demo:

* Internet Connection
* Terminal & Browser
* [Git](https://git-scm.com/) & [GitHub Account](https://github.com)
* [Docker](https://www.docker.com/) with [Docker Compose Plugin](https://docs.docker.com/compose/)
* Your Favorite IDE or Editor

## Technology

This small demo sits on top of following technologies:

* [PHP >= 8.2](https://www.php.net/releases/8.2/en.php)
* [Symfony 7.1 incl. Twig, Asset Mapper & UX](https://symfony.com/)
* [Bootstrap 5](https://getbootstrap.com/docs/5.0/getting-started/introduction/)
* [OpenAI's GPT & Embeddings](https://platform.openai.com/docs/overview)
* [ChromaDB Vector Store](https://www.trychroma.com/)

## Setup

The setup is split into two parts, the Symfony application and the OpenAI configuration.

### 1. Symfony App

Checkout the repository, start the docker environment and install dependencies:
```shell
git clone git@github.com:php-llm/symfony-demo.git
cd symfony-demo
docker compose up -d
docker compose exec app composer install
```

Now you should be able to open https://localhost:8080 in your browser,
and the chatbot UI should be available for you to start chatting.

### 2. OpenAI Configuration

For using GPT and embedding models from OpenAI, you need to configure the `OPENAI_API_KEY` env variable.

This is done by creating a `.env.local` and defining the variable with a valid value.

Verify the success of this step by running the following command:
```shell
docker compose exec app bin/console debug:dotenv
```

You should see the `OPENAI_API_KEY` in the list of secrets.

**Don't forget to set up the project in your favorite IDE or editor.** 

## Functionality

* The chatbot application is a simple and small Symfony 7.1 application.
* The UI is coupled to a Twig LiveComponent, that integrates a `Chat` implementation on top of the user's session.
* You can reset the chat context by hitting the `Reset` button in the top right corner.
* You find three different usage scenarios in the upper navbar.
