import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

/// Ecran des Regles de Confidentialite et Mentions Legales (requis pour le Play Store)
class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Confidentialite & Donnees'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () {
            if (context.canPop()) {
              context.pop();
            } else {
              context.go('/server');
            }
          },
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Politique de Confidentialite - QuizzApp',
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Derniere mise a jour : 29 Aout 2026',
              style: TextStyle(color: Colors.white54, fontSize: 12),
            ),
            const Divider(height: 32, color: Colors.white24),
            _buildSection(
              title: '1. Donnees collectees',
              content:
                  'QuizzApp collecte uniquement les donnees strictement necessaires au fonctionnement du jeu :\n'
                  '- Nom d\'utilisateur (pseudonyme de jeu)\n'
                  '- Adresse e-mail (pour l\'authentification et la recuperation de compte)\n'
                  '- Statistiques de jeu (score, XP, niveau, historique des parties et duels).\n\n'
                  'Aucune donnee de geolocalisation, contact ou identifiant publicitaire n\'est collectee.',
            ),
            _buildSection(
              title: '2. Modele Auto-Heberge (Serveur au choix)',
              content:
                  'QuizzApp est une application cliente compatible avec toute instance de serveur QuizzApp auto-hebergee. '
                  'Vos donnees sont envoyees et stockees exclusivement sur le serveur que vous avez configure dans l\'application.',
            ),
            _buildSection(
              title: '3. Utilisation de l\'Intelligence Artificielle',
              content:
                  'Les questions de quiz sont generees dynamiquement par des modeles d\'IA (Mistral, Groq, OpenRouter). '
                  'Aucune donnee personnelle de l\'utilisateur n\'est envoyee aux fournisseurs d\'IA ; seuls les themes et categories selectionnes sont transmis.',
            ),
            _buildSection(
              title: '4. Vos Droits (RGPD / Suppression de compte)',
              content:
                  'Vous pouvez a tout moment demander la modification ou la suppression integrale de votre compte et de votre historique '
                  'directement depuis les parametres de votre compte ou en contactant l\'administrateur de votre instance serveur.',
            ),
            _buildSection(
              title: '5. Securite',
              content:
                  'Les mots de passe sont haches de maniere securisee (BCrypt). '
                  'Les communications avec les serveurs sont chiffrees via HTTPS et WebSockets securises (WSS).',
            ),
            const SizedBox(height: 20),
            Center(
              child: ElevatedButton(
                onPressed: () {
                  if (context.canPop()) {
                    context.pop();
                  } else {
                    context.go('/server');
                  }
                },
                child: const Text('J\'ai compris'),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildSection({required String title, required String content}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Color(0xFFC4B5FD),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            content,
            style: const TextStyle(color: Colors.white70, height: 1.5, fontSize: 14),
          ),
        ],
      ),
    );
  }
}
