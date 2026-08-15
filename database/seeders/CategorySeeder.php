<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\AllowedTopic;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'History & Geography' => [
                'Ancient History' => ['Pharaonic Civilization', 'Roman Empire', 'Ancient Greek Civilization', 'Maya Civilization', 'Ancient Mythology'],
                'Modern History' => ['World War I', 'World War II', 'Industrial Revolution', 'Cold War', 'Discovery of the Americas'],
                'World Geography' => ['Country Capitals', 'Rivers and Lakes', 'Mountain Ranges', 'Oceans', 'Deserts'],
                'Landmarks & Tourism' => ['Seven Wonders of the World', 'Global Museums', 'Historic Castles and Palaces', 'Ancient Temples'],
            ],
            'Science & Nature' => [
                'Astronomy & Space' => ['Solar System Planets', 'Black Holes', 'Galaxies', 'Space Exploration', 'Satellites'],
                'Biology' => ['Human Body Systems', 'Botany', 'Zoology', 'Genetics', 'Cell Biology'],
                'Chemistry' => ['Periodic Table', 'Chemical Reactions', 'Organic Compounds', 'Acids and Bases', 'States of Matter'],
                'Earth Sciences & Weather' => ['Earthquakes and Volcanoes', "Earth's Layers", 'Water Cycle', 'Climate Change'],
            ],
            'Arts & Literature' => [
                'World Literature' => ['Classic Novels', 'Poetry', 'Plays', 'Science Fiction Literature', 'Nobel Laureates in Literature'],
                'Visual Arts' => ['Renaissance Art', 'Impressionism', 'Abstract Art', 'Famous Paintings and Painters'],
                'Cinema & TV' => ['Academy Awards (Oscars)', 'History of Cinema', 'Film Directors', 'Cinematography Techniques'],
                'Music' => ['Classical Music', 'Musical Instruments', 'Music Theory', 'Famous Composers'],
            ],
            'Sports & Health' => [
                'Global Tournaments' => ['FIFA World Cup', 'Olympic Games', 'Grand Slam Tennis Tournaments', 'Formula 1 Racing'],
                'Individual Sports' => ['Swimming', 'Athletics (Track and Field)', 'Gymnastics', 'Boxing', 'Weightlifting'],
                'Nutrition' => ['Vitamins and Minerals', 'Proteins and Carbohydrates', 'Healthy Diets', 'Calorie Counting'],
                'Public Health' => ['First Aid', 'Immune System', 'Viruses and Bacteria', 'Mental Health'],
            ],
            'Technology & Programming' => [
                'Programming Fundamentals' => ['Variables', 'Loops', 'Conditionals', 'Basic Algorithms', 'Data Structures'],
                'Programming Languages' => ['Python', 'JavaScript', 'C++', 'Java', 'Swift'],
                'Web Development' => ['HTML and CSS Basics', 'UI/UX Design', 'Servers', 'Hosting and Domains', 'Web Frameworks'],
                'Databases' => ['SQL', 'Relational Databases', 'NoSQL', 'Database Queries', 'Data Backups'],
                'Cybersecurity' => ['Encryption', 'Social Engineering', 'Malware', 'Firewalls', 'Two-Factor Authentication (2FA)'],
                'Mobile App Development' => ['iOS Development', 'Android Development', 'Cross-Platform Frameworks (Flutter/React Native)', 'Mobile UI Design', 'App Store Publishing'],
                'Artificial Intelligence & Machine Learning' => ['Neural Networks', 'Natural Language Processing (NLP)', 'Computer Vision', 'Deep Learning', 'Predictive Analytics'],
                'Cloud Computing & DevOps' => ['Cloud Service Providers (AWS/Azure/GCP)', 'Containerization (Docker)', 'Continuous Integration and Deployment (CI/CD)', 'Serverless Architecture', 'Kubernetes'],
                'Software Engineering & Architecture' => ['Object-Oriented Programming (OOP)', 'Software Design Patterns', 'Agile Methodology', 'Microservices Architecture', 'Software Testing and QA'],
            ],
        ];

        foreach ($data as $categoryName => $subcategories) {
            $category = Category::create(['name' => $categoryName]);

            foreach ($subcategories as $subcategoryName => $topics) {
                $subcategory = Subcategory::create([
                    'name' => $subcategoryName,
                    'category_id' => $category->id,
                ]);

                foreach ($topics as $topicName) {
                    AllowedTopic::create([
                        'subcategory_id' => $subcategory->id,
                        'topic_name' => $topicName,
                    ]);
                }
            }
        }
    }
    
}
